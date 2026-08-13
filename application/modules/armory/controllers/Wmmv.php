<?php
class Wmmv extends MX_Controller {
    public function proxy() {
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        if (!preg_match('#/armory/wmmv/proxy/(.+)$#', $uri, $m)) {
            http_response_code(404); exit;
        }
        $path = trim($m[1], '/');

        if (strpos($path, '..') !== false) {
            http_response_code(403); exit;
        }

        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $allowed = ['json', 'png', 'js', 'webp', 'mo3', 'skin', 'anim', 'phys', 'bone', 'skel', 'm2', 'blp', 'mp3', 'ogg'];
        if (!in_array($ext, $allowed)) {
            http_response_code(403); exit;
        }

        $mimes = [
            'json' => 'application/json',
            'png'  => 'image/png',
            'webp' => 'image/webp',
            'js'   => 'application/javascript',
            'mp3'  => 'audio/mpeg',
            'ogg'  => 'audio/ogg',
        ];

        $cacheDir  = APPPATH . 'modules/armory/assets/wmmv-cache/' . dirname($path);
        $cacheFile = APPPATH . 'modules/armory/assets/wmmv-cache/' . $path;

        @ini_set('zlib.output_compression', 'Off');
        if (function_exists('apache_setenv')) {
            apache_setenv('no-gzip', 1);
            apache_setenv('dontgzip', 1);
        }
        while (ob_get_level() > 0) { ob_end_clean(); }

        if (file_exists($cacheFile)) {
            $cachedExt = strtolower(pathinfo($cacheFile, PATHINFO_EXTENSION));
            $mime = isset($mimes[$cachedExt]) ? $mimes[$cachedExt] : 'application/octet-stream';
            header('Content-Type: ' . $mime);
            header('Content-Length: ' . filesize($cacheFile));
            header('Access-Control-Allow-Origin: *');
            header('Cache-Control: public, max-age=86400');
            readfile($cacheFile);
            exit;
        }

        // Tentative 1 : Wowhead CDN (métadonnées, charactercustomization)
        list($content, $httpCode, $servedExt) = $this->fetchUrl(
            'https://wow.zamimg.com/modelviewer/classic/' . $path, $ext
        );

        // Tentative 2 : Warmane CDN (modèles MO3, textures, armor metadata)
        if ($content === false || $httpCode !== 200) {
            list($content, $httpCode, $servedExt) = $this->fetchUrl(
                'https://cdn.warmane.com/wmmv/' . $path, $ext
            );
        }

        // Tentative 3 : fallback webp → png
        if (($content === false || $httpCode !== 200) && $ext === 'webp') {
            $pngPath = substr($path, 0, -4) . 'png';
            list($content, $httpCode, $servedExt) = $this->fetchUrl(
                'https://cdn.warmane.com/wmmv/' . $pngPath, 'png'
            );
            if ($content !== false && $httpCode === 200) {
                $cacheDir  = APPPATH . 'modules/armory/assets/wmmv-cache/' . dirname($pngPath);
                $cacheFile = APPPATH . 'modules/armory/assets/wmmv-cache/' . $pngPath;
            }
        }

        if ($content === false || $httpCode !== 200) {
            // Texture manquante (ex: IDs locaux v2001 comme 1, 19) : retourner un pixel blanc
            // PNG 1×1 immédiatement. Sans ça, le navigateur déclenche onerror après plusieurs
            // secondes de timeout → Ph.l=null pendant ce temps → Oh.E=false → batch bloqué →
            // canvas transparent malgré les draw calls des autres batches.
            if (in_array($ext, ['png', 'webp', 'blp'])) {
                // PNG 1×1 blanc opaque (RGBA 255,255,255,255)
                $fallbackPng = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAAC0lEQVQI12NgAAIABQAABjE+ibYAAAAASUVORK5CYII=');
                header('Content-Type: image/png');
                header('Content-Length: ' . strlen($fallbackPng));
                header('Access-Control-Allow-Origin: *');
                echo $fallbackPng;
                exit;
            }
            http_response_code($httpCode ?: 502);
            exit;
        }

        // ── MO3 : conversion v2001 (Warmane) → retail ──────────────────────────────
        // Warmane CDN sert des MO3 avec un header de 108 bytes (27 uint32).
        // Le viewer Wowhead attend un header retail de 156 bytes (39 uint32).
        // Détection : zlib magic 0x78 à l'offset 108 (Warmane) et pas à l'offset 156.
        // Fix : insérer 48 bytes (12 uint32) à l'offset 108 pour passer en format retail.
        if ($ext === 'mo3' && strlen($content) > 156) {
            if (ord($content[108]) === 0x78 && ord($content[156]) !== 0x78) {
                // 12 champs retail-only :
                //   C,M,S,F,k,I,D,U,R,B,O = 0x7FFFFFFF → OOB dans le buffer décompressé
                //   → le viewer lira count=0 pour toutes les sections retail-only
                //   P = 0 → condition "if(G.length < P)" = "if(N < 0)" toujours fausse
                //   → le parsing du buffer décompressé s'exécute normalement
                $insert = pack('V12',
                    0x7FFFFFFF, 0x7FFFFFFF, 0x7FFFFFFF, 0x7FFFFFFF,
                    0x7FFFFFFF, 0x7FFFFFFF, 0x7FFFFFFF, 0x7FFFFFFF,
                    0x7FFFFFFF, 0x7FFFFFFF, 0x7FFFFFFF, 0x00000000
                );
                $content = substr($content, 0, 108) . $insert . substr($content, 108);

                // Warmane v2001 section swap : le buffer décompressé commence par les vertex
                // (count à byte 0, données de byte 4 à byte 338211), puis les indices
                // (count = field[3] original, données de field[3]+4 à field[4]-1).
                // Le viewer retail attend : field[3] = vertex offset, field[4] = index offset.
                // Fix : field[3] ← 0 (vertex au début du buffer), field[4] ← ancienne valeur field[3].
                $old_field3 = unpack('V', substr($content, 12, 4))[1];
                $content = substr_replace($content, pack('V', 0), 12, 4);
                $content = substr_replace($content, pack('V', $old_field3), 16, 4);
                // field[6] (submesh section) : en v2001, la section à l'ancien field[4]=393704
                // contient count=1 (un seul submesh). On redirige field[6] vers elle.
                // (le viewer lit field[6] comme la liste de submeshes Sh)
                $old_field4 = unpack('V', substr($content, 16, 4))[1]; // = old_field3 = 338212... non
                // old_field4 est l'ANCIENNE valeur de field[4] = 393704 (avant le swap)
                // Après notre swap, field[4]=338212 (index). L'ancien field[4]=393704 est orphelin.
                // On met ce 393704 dans field[6] pour que le viewer lise le submesh là.
                $orig_field4 = unpack('V', substr($content, 16, 4))[1]; // = 338212 (swapped)
                // On ne peut pas récupérer 393704 directement après le swap.
                // On le calcule : field[5] - 8 = 393712 - 8 = 393704
                $field5 = unpack('V', substr($content, 20, 4))[1]; // field[5] = 393712
                $submesh_off = $field5 - 8; // = 393704 (8 octets avant la section r)
                $content = substr_replace($content, pack('V', $submesh_off), 24, 4); // field[6]

                // Swap field[11] (byte 44) ↔ field[12] (byte 48)
                // En v2001, les Ih render batches sont à field[11] (où retail attend Wr).
                // La section "Wr" de retail est à field[12] et est vide (K=0) en v2001.
                // Après swap : Bh.L (Ih) lit les vrais render batches ; Bh.e (Wr) sera synthétisé en JS.
                $f11_bytes = substr($content, 44, 4);
                $f12_bytes = substr($content, 48, 4);
                $content = substr_replace($content, $f12_bytes, 44, 4); // field[11] = ex-field[12]
                $content = substr_replace($content, $f11_bytes, 48, 4); // field[12] = ex-field[11]
            }
        }

        // ── viewer.min.js : patches minimaux ───────────────────────────────────────
        if ($servedExt === 'js' && strpos($path, 'viewer.min.js') !== false) {

            // rn (DataView wrapper) : bounds-check + caps position > 1M
            // Les sections animation Warmane sont à ~26M dans le buffer décompressé.
            // À ces positions, les counts lus peuvent être énormes → OOM.
            // Cap : getInt32/getUint32 > 65535 → 0 ; getInt16/getInt8 négatif → 0.
            $rn_patches = [
                'getBool(){var t=0!=this.buffer.getUint8(this.position);return this.position+=1,t}'
                    => 'getBool(){var t=this.position+1>this.buffer.byteLength?false:0!=this.buffer.getUint8(this.position);return this.position+=1,t}',
                'getUint8(){var t=this.buffer.getUint8(this.position);return this.position+=1,t}'
                    => 'getUint8(){var t=this.position+1>this.buffer.byteLength?0:this.buffer.getUint8(this.position);return this.position+=1,t}',
                'getInt8(){var t=this.buffer.getInt8(this.position);return this.position+=1,t}'
                    => 'getInt8(){var p=this.position,t=p+1>this.buffer.byteLength?0:this.buffer.getInt8(p);return this.position+=1,p>1e6&&t<0&&(t=0),t}',
                'getUint16(){var t=this.buffer.getUint16(this.position,!0);return this.position+=2,t}'
                    => 'getUint16(){var t=this.position+2>this.buffer.byteLength?0:this.buffer.getUint16(this.position,!0);return this.position+=2,t}',
                'getInt16(){var t=this.buffer.getInt16(this.position,!0);return this.position+=2,t}'
                    => 'getInt16(){var p=this.position,t=p+2>this.buffer.byteLength?0:this.buffer.getInt16(p,!0);return this.position+=2,p>1e6&&t<0&&(t=0),t}',
                'getUint32(){var t=this.buffer.getUint32(this.position,!0);return this.position+=4,t}'
                    => 'getUint32(){var p=this.position,t=p+4>this.buffer.byteLength?0:this.buffer.getUint32(p,!0);return this.position+=4,p>1e6&&t>65535&&(t=0),t}',
                'getInt32(){var t=this.buffer.getInt32(this.position,!0);return this.position+=4,t}'
                    => 'getInt32(){var p=this.position,t=p+4>this.buffer.byteLength?0:this.buffer.getInt32(p,!0);return this.position+=4,p>1e6&&(t<0||t>65535)&&(t=0),t}',
                'getFloat(){var t=this.buffer.getFloat32(this.position,!0);return this.position+=4,t}'
                    => 'getFloat(){var t=this.position+4>this.buffer.byteLength?0:this.buffer.getFloat32(this.position,!0);return this.position+=4,t}',
            ];
            foreach ($rn_patches as $old => $new) {
                $p = str_replace($old, $new, $content);
                if ($p !== $content) { $content = $p; }
            }

            // wr.a : cap count + filter undefined après break
            // Le break sur exception laisse des trous (undefined) dans this.d.
            // wr.b et wr.e accèdent à this.d[t].prop sans vérifier → TypeError.
            // Filter supprime les trous pour que this.d[t] soit toujours défini.
            $p = str_replace(
                'a(t,e){var i=t.getInt32();this.d=new Array(i);for(let s=0;s<i;++s)this.d[s]=new e(t)}',
                'a(t,e){var i=t.getInt32();if(i<0||i>65535)i=0;this.d=new Array(i);for(let s=0;s<i;++s){try{this.d[s]=new e(t)}catch(ex){break}}this.d=this.d.filter(function(x){return null!=x})}',
                $content
            );
            if ($p !== $content) { $content = $p; }

            // wr.b : guard this.d[t] (sécurité au cas où le filter ne suffit pas)
            $p = str_replace(
                'b(t){return!(!this.d||0==this.d.length)&&(t>=this.d.length&&(t=0),this.d[t].l)}',
                'b(t){return!(!this.d||0==this.d.length)&&(t>=this.d.length&&(t=0),this.d[t]&&this.d[t].l)}',
                $content
            );
            if ($p !== $content) { $content = $p; }

            // Sh : cap du count direct (loop sans wr.a)
            $p = str_replace(
                'var e=t.getInt32();this.n=new Array(e);for(var i=0;i<e;i++)this.n[i]=Pe(t.getFloat(),t.getFloat(),t.getFloat())',
                'var e=t.getInt32();if(e<0||e>65535)e=0;this.n=new Array(e);for(var i=0;i<e;i++)this.n[i]=Pe(t.getFloat(),t.getFloat(),t.getFloat())',
                $content
            );
            if ($p !== $content) { $content = $p; }

            // Ar.t : bone parent lookup guard
            // i.A.O[e.e] peut être undefined si wr.a a stoppé avant la fin (try/catch break)
            // → TypeError: Cannot read properties of undefined (reading 't')
            $p = str_replace(
                'e.e>-1){i.A.O[e.e].t(t);let s=this.e;if(ni(s,i.A.O[e.e].j)',
                'e.e>-1&&i.A.O[e.e]){i.A.O[e.e].t(t);let s=this.e;if(ni(s,i.A.O[e.e].j)',
                $content
            );
            if ($p !== $content) { $content = $p; }

            // Ar loop : même guard sur le .p(t) dans la boucle juste avant
            $p = str_replace(
                'for(let i=0;i<e.length;i++)this.A.O[e[i]].p(t)}',
                'for(let i=0;i<e.length;i++)this.A.O[e[i]]&&this.A.O[e[i]].p(t)}',
                $content
            );
            if ($p !== $content) { $content = $p; }

            // Texture filter : 0=absent, 65535=sentinel WoW Classic.
            // On retire le filtre e>10000 pour permettre les IDs v2001 (1-9999).
            // Le log Ph_tex= permet de voir quels IDs le MO3 déclare.
            $p = str_replace(
                '0!=e&&(this.a=t.options.contentPath+"textures/"+e+',
                '0!=e&&65535!=e&&(console.log("Ph_tex="+e),this.a=t.options.contentPath+"textures/"+e+',
                $content
            );
            if ($p !== $content) { $content = $p; }

            // Diagnostic logs Bh : log vertex/index/submesh counts pour confirmer le fix v2001
            $p = str_replace(
                'for(let t=0;t<N;++t)this.G[t]=e.getUint16()}e.position=r;',
                'for(let t=0;t<N;++t)this.G[t]=e.getUint16()}console.log("Bh verts="+L+" idx="+N);e.position=r;',
                $content
            );
            if ($p !== $content) { $content = $p; }

            $p = str_replace(
                '}e.position=a;var q=e.getInt32();if(q>0){this.l=new Array(q)',
                '}console.log("Bh subs="+H);e.position=a;var q=e.getInt32();if(q<0||q>65535)q=0;console.log("Bh q="+q);if(q>0){this.l=new Array(q)',
                $content
            );
            if ($p !== $content) { $content = $p; }

            // Diagnostic log jh : confirme que le modèle atteint l'état "chargé"
            // Forcer doUpdateBounds répétitivement pendant 2s pour que la caméra se repositionne
            // APRÈS que le bone guard ait mis les bones en T-pose (frame 1+).
            // Sans ça : bbox calculé avec bones invalides → caméra mal placée → transparent.
            $p = str_replace(
                'this.Q.doUpdateBounds=!0,this.f=!0',
                'this.Q.doUpdateBounds=!0,console.log("jh loaded!"),(function(){var _jh=this;var _cnt=0;var _iv=setInterval(function(){_jh.Q.doUpdateBounds=!0;if(++_cnt>=20)clearInterval(_iv);},100);}).call(this),setTimeout(function(){console.log("_bfx="+window._bfx);},2000),this.f=!0',
                $content
            );
            if ($p !== $content) { $content = $p; }

            // Bh constructor : injecter Wr synthétiques (Bh.e) et blend lookup (Bh.z) pour v2001.
            // Après le swap field[11]↔[12] : Bh.L a 59 vrais Ih, mais Bh.e (Wr) = [] (section vide).
            // Oh.k() crashe sur Bh.e[Ih.k]=undefined → créer 57 Wr minimaux + Bh.z pour charger textures.
            $p = str_replace(
                'if(K>0){this.L=new Array(K);for(let t=0;t<K;++t)this.L[t]=new Ih(e)}',
                'if(K>0){this.L=new Array(K);for(let t=0;t<K;++t)this.L[t]=new Ih(e)}' .
                'if(!this.e.length&&this.L.length>0&&this.G.length>0){const _hn=this.G.length>>1;this.e=Array.from({length:57},function(){return{a:_hn,k:0,c:0,d:0,b:0,e:0,i:0,j:[0,0,0],h:[0,0,0],g:1};});console.log("Bh.e synth len="+this.e.length+" drawHalf="+_hn);}' .
                'if(this.F.length>0&&this.L.length>0){const _fl=this.F.length;this.z=Array.from({length:Math.max(64,this.L.length*2)},function(x,idx){return idx%_fl;});console.log("Bh.z force len="+this.z.length);}',
                $content
            );
            if ($p !== $content) { $content = $p; }

            // Bh submesh count cap : évite la boucle massive (ex: 524837 Sh) quand field[6]
            // pointe vers des données parasites dans les MO3 d'armure v2001.
            // Le fallback synthétique ci-dessous gère le cas où H=0 après cap.
            $p = str_replace(
                'e.position=n;var H=e.getInt32();if(H>0){this.x=new Array(H);for(let t=0;t<H;++t)this.x[t]=new Sh(e)}',
                'e.position=n;var H=e.getInt32();if(H<0||H>65535)H=0;if(H>0){this.x=new Array(H);for(let t=0;t<H;++t)this.x[t]=new Sh(e)}',
                $content
            );
            if ($p !== $content) { $content = $p; }

            // Fallback submesh synthétique (Bh constructor)
            // Si this.x (submeshes) est vide ou n'a aucun entry avec start_index < G.length,
            // créer un submesh couvrant tous les indices pour garantir un rendu visible.
            // (En v2001, la section submesh est petite et le viewer peut lire 0 submesh valides.)
            $p = str_replace(
                'new Fh(e)}}}}const Oh=',
                'new Fh(e)};var _bh=this;if(_bh.G.length>0&&(!_bh.x.length||!_bh.x.some(function(s){return s&&s.i<_bh.G.length&&s.d>0})))_bh.x=[{m:0,a:0,i:0,d:_bh.G.length,b:0,j:0,c:0,h:[0,0,0],e:[0,0,0],f:0,k:0,l:void 0,g:function(){}}];}}}const Oh=',
                $content
            );
            if ($p !== $content) { $content = $p; }

            // He/qe (minVec/maxVec) : guard undefined 3e arg
            // jh.aH() appelle He(a, a, this.m?.c?.c?.h) — si pas de monture,
            // this.m est null → chain optional → undefined → TypeError sur undefined[0]
            // FIX: copier e dans t (bounding box du perso seul) plutôt que retourner t inchangé
            // (retourner t inchangé laissait le bbox à [9999,9999,999]/[-9999,-9999,-9999] → caméra vide)
            $p = str_replace(
                'function He(t,e,i){return t[0]=Math.min(e[0],i[0]),t[1]=Math.min(e[1],i[1]),t[2]=Math.min(e[2],i[2]),t}',
                'function He(t,e,i){if(void 0===i||null===i){return t[0]=e[0],t[1]=e[1],t[2]=e[2],t}return t[0]=Math.min(e[0],i[0]),t[1]=Math.min(e[1],i[1]),t[2]=Math.min(e[2],i[2]),t}',
                $content
            );
            if ($p !== $content) { $content = $p; }

            $p = str_replace(
                'function qe(t,e,i){return t[0]=Math.max(e[0],i[0]),t[1]=Math.max(e[1],i[1]),t[2]=Math.max(e[2],i[2]),t}',
                'function qe(t,e,i){if(void 0===i||null===i){return t[0]=e[0],t[1]=e[1],t[2]=e[2],t}return t[0]=Math.max(e[0],i[0]),t[1]=Math.max(e[1],i[1]),t[2]=Math.max(e[2],i[2]),t}',
                $content
            );
            if ($p !== $content) { $content = $p; }

            // Blend mode : le switch couvre les cases 0-13 mais throw sur valeur inconnue.
            // Les données animation v2001 garbage peuvent produire des indices > 13 → crash.
            // Remplacer le throw par break pour ignorer silencieusement.
            $p = str_replace(
                'case 13:e.blendFuncSeparate(e.ONE,e.ONE_MINUS_SRC_ALPHA,e.ONE,e.ONE);break;default:throw 3735927486',
                'case 13:e.blendFuncSeparate(e.ONE,e.ONE_MINUS_SRC_ALPHA,e.ONE,e.ONE);break;default:break',
                $content
            );
            if ($p !== $content) { $content = $p; }

            // gf.forEach : t.G peut être undefined pour certains slots de personnalisation v2001.
            // Accéder t.G[0] sans guard → TypeError "Cannot read properties of undefined (reading '0')".
            $p = str_replace(
                'if(t&&t.v&&t.L)for(let e of t.L){if(!e)continue;let i=e.e;1==t.q?(i.i(t.G[0]',
                'if(t&&t.v&&t.L&&t.G)for(let e of t.L){if(!e)continue;let i=e.e;1==t.q?(i.i(t.G[0]',
                $content
            );
            if ($p !== $content) { $content = $p; }

            // Ph onerror fallback : quand une texture fait 404 (ex: IDs v2001 locaux comme 1, 19),
            // Ph.l reste null → Oh.E = false → le batch entier ne dessine jamais → transparent.
            // Fix : créer un canvas 2D 1×1 blanc et appeler this.c() pour forger une vraie texture WebGL.
            // Résultat : Oh.E = true pour tous les batches → batches avec textures manquantes dessinés en blanc.
            $p = str_replace(
                'this.e.onload=()=>{this.c()},this.e.onerror=()=>{this.e=null},this.b(this.e,this.a)',
                'this.e.onload=()=>{this.c()},this.e.onerror=()=>{const _ec=document.createElement("canvas");_ec.width=1;_ec.height=1;const _ec2=_ec.getContext("2d");_ec2.fillStyle="#fff";_ec2.fillRect(0,0,1,1);this.e=_ec;this.c();},this.b(this.e,this.a)',
                $content
            );
            if ($p !== $content) { $content = $p; }

            // Bone world matrix : forcer TOUS les bones à l'identité (T-pose complète).
            // Raison : les données animation v2001 sont lues comme float32 garbage → matrices
            // invalides → vertices projetés à des positions quelconques → bbox garbage →
            // caméra mal placée → canvas transparent.
            // En forçant tous les bones à l'identité dès la frame 1, le bbox initial est
            // calculé sur les vertices en T-pose → caméra bien positionnée → personnage visible.
            $p = str_replace(
                'ui(i.j,i.j,h);let l=120&e.b;',
                'ui(i.j,i.j,h);{const _ij=i.j;if(!window._bfx)window._bfx=0;window._bfx++;_ij[0]=1,_ij[1]=0,_ij[2]=0,_ij[3]=0,_ij[4]=0,_ij[5]=1,_ij[6]=0,_ij[7]=0,_ij[8]=0,_ij[9]=0,_ij[10]=1,_ij[11]=0,_ij[12]=0,_ij[13]=0,_ij[14]=0,_ij[15]=1;}let l=120&e.b;',
                $content
            );
            if ($p !== $content) { $content = $p; }
        }

        if (!is_dir($cacheDir)) {
            @mkdir($cacheDir, 0755, true);
        }
        @file_put_contents($cacheFile, $content);

        $mime = isset($mimes[$servedExt]) ? $mimes[$servedExt] : 'application/octet-stream';
        header('Content-Type: ' . $mime);
        header('Content-Length: ' . strlen($content));
        header('Access-Control-Allow-Origin: *');
        header('Cache-Control: public, max-age=86400');
        echo $content;
        exit;
    }

    private function fetchUrl($url, $ext) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $content  = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return [$content, $httpCode, $ext];
    }
}
