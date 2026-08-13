<link rel="stylesheet" href="<?= base_url('application/modules/armory/assets/css/armory.css'); ?>">
<script src="https://code.jquery.com/jquery-3.6.0.min.js" crossorigin="anonymous"></script>

<?php foreach ($realms as $charsMultiRealm):
    $MultiRealm = $this->wowrealm->getRealmConnectionData($charsMultiRealm->id);
    $playerResult = $this->armory_model->getPlayerInfo($MultiRealm, $id)->result();
    if (empty($playerResult)) continue;
    $info     = $playerResult[0];
    $items      = $this->armory_model->getAllEquipment($MultiRealm, $id);
    $itemData   = $this->armory_model->getItemData(array_values($items), $charsMultiRealm->id);
    $playTimeSec = $this->armory_model->getPlayTime($MultiRealm, $id);
    $ptDays  = (int)floor($playTimeSec / 86400);
    $ptHours = (int)floor(($playTimeSec % 86400) / 3600);
    $ptLabel = $ptDays > 0 ? "{$ptDays} j {$ptHours} h" : "{$ptHours} h";
    $avgIlvl    = $this->armory_model->getAverageIlvl(array_values($items), $charsMultiRealm->id);
    $guildName  = $this->armory_model->getGuildName($MultiRealm, (int)$id);
    $reputation = $this->armory_model->getReputation($MultiRealm, $id);
    $talentSpells = $this->armory_model->getCharacterTalentSpells($MultiRealm, $id);
    $talentTabs   = $this->armory_model->getTalentTabsForClass((int)$info->class, $charsMultiRealm->id);
    $allTalents   = $this->armory_model->getTalentsForClass((int)$info->class, $charsMultiRealm->id);
    $activeSpec   = isset($info->activeTalentGroup) ? (int)$info->activeTalentGroup : 0;

    // Calcule pour chaque spec : talentId → rang actuel
    $specData = [];
    for ($sp = 0; $sp <= 1; $sp++) {
        $spells = $talentSpells[$sp] ?? [];
        if (empty($spells)) continue;
        $ranks = [];
        foreach ($allTalents as $t) {
            for ($r = 9; $r >= 1; $r--) {
                $rKey = 'SpellRank_' . $r;
                if (!empty($t[$rKey]) && isset($spells[(int)$t[$rKey]])) {
                    $ranks[(int)$t['ID']] = $r;
                    break;
                }
            }
        }
        $specData[$sp] = $ranks;
    }

    $totalStats = $this->armory_model->getTotalStats(
        array_values(array_filter(array_map('intval', $items))),
        $charsMultiRealm->id
    );

    $statLabels = [
        0=>'Mana', 1=>'Santé', 3=>'Agilité', 4=>'Force', 5=>'Intelligence',
        6=>'Esprit', 7=>'Endurance', 12=>'Défense', 13=>'Esquive', 14=>'Parade',
        15=>'Blocage', 31=>'Toucher', 32=>'Critique', 35=>'Résilience',
        36=>'Hâte', 37=>'Expertise', 38=>"Puissance d'attaque", 39=>'PA distance',
        43=>'Régén. mana', 44=>"Pén. d'armure", 45=>'Puissance sorts',
        46=>'Régén. santé', 47=>'Pén. de sorts', 48=>'Valeur blocage',
    ];
    $primaryStatIds  = [7, 4, 3, 5, 6];
    $statOrderLeft   = [7, 4, 3, 5, 6, 43];
    $statOrderRight  = [45, 38, 39, 36, 32, 31, 37, 44, 47, 35, 12, 13, 14, 15, 48];

    $slotIcons = [
        0=>'head.gif', 1=>'neck.gif', 2=>'shoulders.gif', 3=>'chest.gif',
        4=>'chest.gif', 5=>'waist.gif', 6=>'legs.gif', 7=>'feet.gif',
        8=>'wrists.gif', 9=>'hands.gif', 10=>'finger.gif', 11=>'finger.gif',
        12=>'trinket.gif', 13=>'trinket.gif', 14=>'neck.gif',
        15=>'mainhand.gif', 16=>'offhand.gif', 17=>'ranged.gif', 18=>'tabard.gif',
    ];
    $slotLabels = [
        0=>'Tête', 1=>'Cou', 2=>'Épaules', 3=>'Chemise', 4=>'Torse',
        5=>'Ceinture', 6=>'Jambes', 7=>'Pieds', 8=>'Poignets', 9=>'Mains',
        10=>'Anneau 1', 11=>'Anneau 2', 12=>'Bijou 1', 13=>'Bijou 2',
        14=>'Cape', 15=>'Main droite', 16=>'Main gauche', 17=>'Distance', 18=>'Tabard',
    ];
    $slotsLeft   = [0, 1, 2, 14, 4, 3, 18, 8];
    $slotsRight  = [9, 5, 6, 7, 10, 11, 12, 13];
    $slotsBottom = [15, 16, 17];
    $frameUrl    = base_url('application/modules/armory/assets/images/default/item_frame.png');
    $defaultDir  = base_url('application/modules/armory/assets/images/default/');
    $zoneBg      = base_url('application/modules/armory/assets/images/' . $info->race . '.png');
    $classIcon   = base_url('assets/images/class/' . $this->wowgeneral->getClassIcon($info->class));
    $raceIcon    = base_url('assets/images/races/' . $this->wowgeneral->getRaceIcon($info->race));
    $classColors = [1=>'#C79C6E',2=>'#F58CBA',3=>'#ABD473',4=>'#FFF569',5=>'#FFFFFF',
                    6=>'#C41F3B',7=>'#0070DE',8=>'#69CCF0',9=>'#9482C9',11=>'#FF7D0A'];
    $classColor  = $classColors[(int)$info->class] ?? '#e2e8f0';
?>

<!-- HERO -->
<div class="pv-hero">
    <div class="pv-hero-overlay"></div>
    <a href="<?= base_url('armory'); ?>" class="pv-back-btn">
        <i class="fas fa-arrow-left"></i> Recherche
    </a>
    <div class="pv-hero-body">
        <div class="pv-hero-avatar">
            <img src="<?= $classIcon; ?>" alt="" class="pv-hero-avatar-img">
        </div>
        <div class="pv-hero-info">
            <h1 class="pv-hero-name"><?= htmlspecialchars($info->name); ?></h1>
            <div class="pv-hero-meta">
                <img src="<?= $raceIcon; ?>" alt="" class="pv-meta-icon">
                <span><?= $this->wowgeneral->getRaceName($info->race); ?></span>
                <span class="pv-sep">·</span>
                <img src="<?= $classIcon; ?>" alt="" class="pv-meta-icon">
                <span><?= $this->wowgeneral->getClassName($info->class); ?></span>
                <span class="pv-sep">·</span>
                <span>Niveau <strong><?= $info->level; ?></strong></span>
            </div>
        </div>
    </div>
    <div class="pv-hero-stats">
        <div class="pv-stat">
            <i class="fas fa-heart" style="color:#e05555"></i>
            <div><span class="pv-stat-val"><?= number_format($info->health, 0, ',', ' '); ?></span><span class="pv-stat-lbl">Santé</span></div>
        </div>
        <div class="pv-stat">
            <i class="fas fa-shield-halved" style="color:#5588e0"></i>
            <div><span class="pv-stat-val"><?= number_format($info->totalHonorPoints, 0, ',', ' '); ?></span><span class="pv-stat-lbl">Honneur</span></div>
        </div>
        <div class="pv-stat">
            <i class="fas fa-khanda" style="color:#e0a855"></i>
            <div><span class="pv-stat-val"><?= number_format($info->arenaPoints, 0, ',', ' '); ?></span><span class="pv-stat-lbl">Arène</span></div>
        </div>
        <div class="pv-stat">
            <i class="fas fa-trophy" style="color:#a855e0"></i>
            <div><span class="pv-stat-val"><?= $this->armory_model->getLogros($MultiRealm, $id); ?></span><span class="pv-stat-lbl">Succès</span></div>
        </div>
        <div class="pv-stat">
            <i class="fas fa-clock" style="color:#00e1ff"></i>
            <div><span class="pv-stat-val"><?= $ptLabel; ?></span><span class="pv-stat-lbl">Temps de jeu</span></div>
        </div>
        <?php if ($avgIlvl > 0): ?>
        <div class="pv-stat">
            <i class="fas fa-shield-halved" style="color:#ffd100"></i>
            <div><span class="pv-stat-val"><?= (int)$avgIlvl; ?></span><span class="pv-stat-lbl">ilvl moyen</span></div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- EQUIPEMENT -->
<div class="pv-equip-wrap" style="background-image:url('<?= $zoneBg; ?>')">
    <div class="pv-equip-container">
        <div class="pv-panel">

            <!-- Stats primaires (extérieur gauche) -->
            <div class="pv-total-stats pv-total-left">
                <?php foreach ($statOrderLeft as $t):
                    if (empty($totalStats[$t])) continue;
                    $isPri = in_array($t, $primaryStatIds);
                ?>
                <div class="pts-row<?= $isPri ? ' pts-primary' : ''; ?>">
                    <span class="pts-label"><?= htmlspecialchars($statLabels[$t] ?? 'Stat '.$t); ?></span>
                    <span class="pts-value"><?= number_format($totalStats[$t], 0, ',', ' '); ?></span>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Colonne gauche -->
            <div class="pv-col pv-col-left">
                <?php foreach ($slotsLeft as $slot):
                    $ie      = $items[$slot] ?? null;
                    $data    = $ie ? ($itemData[$ie] ?? []) : [];
                    $icon    = $data['icon'] ?? null;
                    $name    = $data['name'] ?? $slotLabels[$slot];
                    $quality = $data['quality'] ?? 1;
                    $qClass  = 'q' . $quality;
                    $src     = $ie ? ($icon ? 'https://wow.zamimg.com/images/wow/icons/large/'.$icon.'.jpg' : $defaultDir.$slotIcons[$slot]) : $defaultDir.$slotIcons[$slot];
                ?>
                <div class="pv-item pv-item-left <?= $ie ? '' : 'pv-item-empty'; ?>">
                    <div class="pv-item-name-wrap">
                        <span class="pv-item-name <?= $ie ? $qClass : ''; ?>"><?= $ie ? htmlspecialchars($name) : $slotLabels[$slot]; ?></span>
                    </div>
                    <div class="pv-item-icon-wrap">
                        <?php if ($ie): ?>
                            <a href="https://fr.wowhead.com/wotlk/item=<?= $ie; ?>" class="pv-item-link" data-item="<?= $ie; ?>" data-realm="<?= (int)$charsMultiRealm->id; ?>" data-slot="<?= $slot; ?>">
                                <img src="<?= $frameUrl; ?>" class="armory-slot-frame" alt="">
                                <img src="<?= $src; ?>" class="armory-slot-icon" alt="<?= $slotLabels[$slot]; ?>">
                            </a>
                        <?php else: ?>
                            <img src="<?= $frameUrl; ?>" class="armory-slot-frame armory-slot-empty" alt="">
                            <img src="<?= $src; ?>" class="armory-slot-icon armory-slot-icon-empty" alt="<?= $slotLabels[$slot]; ?>">
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Centre : portrait uniquement -->
            <div class="pv-center">
                <div class="pv-portrait">
                    <div class="pv-portrait-icon-wrap">
                        <img src="<?= $raceIcon; ?>" alt="" class="pv-portrait-race">
                        <img src="<?= $classIcon; ?>" alt="" class="pv-portrait-class">
                    </div>
                    <h2 class="pv-portrait-name" style="--class-color:<?= $classColor; ?>"><?= htmlspecialchars($info->name); ?></h2>
                    <div class="pv-portrait-sub">
                        <span>Niveau <strong><?= $info->level; ?></strong></span>
                        <span class="pv-portrait-guild"><?= htmlspecialchars($guildName ?? 'Mercenaire'); ?></span>
                    </div>
                </div>
            </div>

            <!-- Colonne droite -->
            <div class="pv-col pv-col-right">
                <?php foreach ($slotsRight as $slot):
                    $ie      = $items[$slot] ?? null;
                    $data    = $ie ? ($itemData[$ie] ?? []) : [];
                    $icon    = $data['icon'] ?? null;
                    $name    = $data['name'] ?? $slotLabels[$slot];
                    $quality = $data['quality'] ?? 1;
                    $qClass  = 'q' . $quality;
                    $src     = $ie ? ($icon ? 'https://wow.zamimg.com/images/wow/icons/large/'.$icon.'.jpg' : $defaultDir.$slotIcons[$slot]) : $defaultDir.$slotIcons[$slot];
                ?>
                <div class="pv-item pv-item-right <?= $ie ? '' : 'pv-item-empty'; ?>">
                    <div class="pv-item-icon-wrap">
                        <?php if ($ie): ?>
                            <a href="https://fr.wowhead.com/wotlk/item=<?= $ie; ?>" class="pv-item-link" data-item="<?= $ie; ?>" data-realm="<?= (int)$charsMultiRealm->id; ?>" data-slot="<?= $slot; ?>">
                                <img src="<?= $frameUrl; ?>" class="armory-slot-frame" alt="">
                                <img src="<?= $src; ?>" class="armory-slot-icon" alt="<?= $slotLabels[$slot]; ?>">
                            </a>
                        <?php else: ?>
                            <img src="<?= $frameUrl; ?>" class="armory-slot-frame armory-slot-empty" alt="">
                            <img src="<?= $src; ?>" class="armory-slot-icon armory-slot-icon-empty" alt="<?= $slotLabels[$slot]; ?>">
                        <?php endif; ?>
                    </div>
                    <div class="pv-item-name-wrap">
                        <span class="pv-item-name <?= $ie ? $qClass : ''; ?>"><?= $ie ? htmlspecialchars($name) : $slotLabels[$slot]; ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Stats secondaires (extérieur droite) -->
            <div class="pv-total-stats pv-total-right">
                <?php foreach ($statOrderRight as $t):
                    if (empty($totalStats[$t])) continue;
                ?>
                <div class="pts-row">
                    <span class="pts-label"><?= htmlspecialchars($statLabels[$t] ?? 'Stat '.$t); ?></span>
                    <span class="pts-value"><?= number_format($totalStats[$t], 0, ',', ' '); ?></span>
                </div>
                <?php endforeach; ?>
            </div>

        </div><!-- /.pv-panel -->

        <!-- Armes -->
        <div class="pv-weapons">
            <?php foreach ($slotsBottom as $slot):
                $ie      = $items[$slot] ?? null;
                $data    = $ie ? ($itemData[$ie] ?? []) : [];
                $icon    = $data['icon'] ?? null;
                $name    = $data['name'] ?? $slotLabels[$slot];
                $quality = $data['quality'] ?? 1;
                $qClass  = 'q' . $quality;
                $src     = $ie ? ($icon ? 'https://wow.zamimg.com/images/wow/icons/large/'.$icon.'.jpg' : $defaultDir.$slotIcons[$slot]) : $defaultDir.$slotIcons[$slot];
            ?>
            <div class="pv-weapon <?= $ie ? '' : 'pv-item-empty'; ?>">
                <div class="pv-weapon-icon">
                    <?php if ($ie): ?>
                        <a href="https://fr.wowhead.com/wotlk/item=<?= $ie; ?>" class="pv-item-link" data-item="<?= $ie; ?>" data-realm="<?= (int)$charsMultiRealm->id; ?>" data-slot="<?= $slot; ?>">
                            <img src="<?= $src; ?>" class="pv-weapon-img" alt="<?= $slotLabels[$slot]; ?>">
                        </a>
                    <?php else: ?>
                        <img src="<?= $src; ?>" class="pv-weapon-img armory-slot-icon-empty" alt="<?= $slotLabels[$slot]; ?>">
                    <?php endif; ?>
                </div>
                <span class="pv-weapon-name <?= $ie ? $qClass : ''; ?>"><?= $ie ? htmlspecialchars($name) : $slotLabels[$slot]; ?></span>
            </div>
            <?php endforeach; ?>
        </div>

    </div>
</div>

<?php
    $equippedEntries = array_values(array_filter(array_map('intval', $items)));
    $gemsBySlot      = $this->armory_model->getEquipmentGems($MultiRealm, $id);
    $enchantsBySlot  = $this->armory_model->getEquipmentEnchants($MultiRealm, $id);
?>
<script>
window._realmItems    = window._realmItems    || {};
window._realmItems[<?= (int)$charsMultiRealm->id; ?>] = new Set([<?= implode(',', $equippedEntries); ?>]);
window._realmGems     = window._realmGems     || {};
window._realmGems[<?= (int)$charsMultiRealm->id; ?>] = <?= json_encode($gemsBySlot); ?>;
window._realmEnchants = window._realmEnchants || {};
window._realmEnchants[<?= (int)$charsMultiRealm->id; ?>] = <?= json_encode($enchantsBySlot); ?>;
</script>

<?php
    // Réputation : on réutilise la dernière valeur de $reputation (dernier realm)
    if (!empty($reputation)):
    $celestiaRep = null;
    $otherReps   = [];
    foreach ($reputation as $r) {
        if (!empty($r['celestia'])) $celestiaRep = $r;
        else $otherReps[] = $r;
    }
    if ($celestiaRep) {
        $cs = (int)$celestiaRep['standing'];
        if      ($cs >= 42000) $cRankClass = 'rep-exalted';
        elseif  ($cs >= 21000) $cRankClass = 'rep-revered';
        elseif  ($cs >= 9000)  $cRankClass = 'rep-honored';
        elseif  ($cs >= 3000)  $cRankClass = 'rep-friendly';
        else                   $cRankClass = 'rep-neutral';
    }
?>
<div class="pv-reputation-wrap">
    <div class="pv-reputation">
        <h2 class="pv-reputation-title"><i class="fas fa-handshake"></i> Réputations</h2>

        <?php if ($celestiaRep): ?>
        <div class="pv-celestia-card">
            <div class="pv-celestia-shimmer"></div>
            <div class="pv-celestia-content">
                <div class="pv-celestia-left">
                    <i class="fas fa-crown pv-celestia-icon"></i>
                    <div>
                        <div class="pv-celestia-name">Celestia</div>
                        <div class="pv-celestia-subtitle">Faction du serveur</div>
                    </div>
                </div>
                <div class="pv-celestia-right">
                    <div class="pv-celestia-standing"><?= number_format($celestiaRep['standing'], 0, ',', ' '); ?></div>
                    <div class="pv-celestia-rank <?= $cRankClass; ?>"><?= $celestiaRep['rank']; ?></div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if (!empty($otherReps)): ?>
        <?php if ($celestiaRep): ?><div class="pv-rep-other-label">Autres factions</div><?php endif; ?>
        <div class="pv-rep-grid">
            <?php foreach ($otherReps as $rep):
                if     ($rep['rank'] === 'Exalté') $rankClass = 'rep-exalted';
                elseif ($rep['rank'] === 'Révéré') $rankClass = 'rep-revered';
                elseif ($rep['rank'] === 'Honoré') $rankClass = 'rep-honored';
                else                               $rankClass = 'rep-friendly';
            ?>
            <div class="pv-rep-item">
                <div class="pv-rep-bar">
                    <div class="pv-rep-name"><?= htmlspecialchars($rep['name']); ?></div>
                    <div class="pv-rep-rank <?= $rankClass; ?>"><?= $rep['rank']; ?></div>
                </div>
                <div class="pv-rep-standing <?= $rankClass; ?>"><?= number_format($rep['standing'], 0, ',', ' '); ?></div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<?php if (!empty($specData) && !empty($talentTabs)): ?>
<div class="pv-talents-wrap">
    <div class="pv-talents">
        <h2 class="pv-talents-title"><i class="fas fa-star"></i> Talents</h2>

        <?php if (count($specData) > 1): ?>
        <div class="tt-spec-nav">
            <?php foreach ($specData as $spIdx => $unused): ?>
            <button class="tt-spec-btn<?= $spIdx === $activeSpec ? ' active' : ''; ?>" data-spec="<?= $spIdx; ?>">
                <?= $spIdx === 0 ? 'Spécialisation principale' : 'Spécialisation secondaire'; ?>
            </button>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php foreach ($specData as $spIdx => $talentRanks): ?>
        <div class="tt-spec<?= ($spIdx !== $activeSpec && count($specData) > 1) ? ' tt-spec-hidden' : ''; ?>" data-spec="<?= $spIdx; ?>">
            <div class="tt-trees">
                <?php foreach ($talentTabs as $tab):
                    $tabId = (int)$tab['ID'];
                    $tabPts = 0;
                    foreach ($allTalents as $t) {
                        if ((int)$t['TabID'] === $tabId) {
                            $tabPts += isset($talentRanks[(int)$t['ID']]) ? $talentRanks[(int)$t['ID']] : 0;
                        }
                    }
                ?>
                <div class="tt-tree">
                    <div class="tt-tree-header">
                        <span class="tt-tree-name"><?= htmlspecialchars($tab['name'] ?? $tab['BackgroundFile']); ?></span>
                        <span class="tt-tree-pts"><?= $tabPts; ?> pts</span>
                    </div>
                    <div class="tt-grid">
                        <?php foreach ($allTalents as $t):
                            if ((int)$t['TabID'] !== $tabId) continue;
                            $tid     = (int)$t['ID'];
                            $rank    = isset($talentRanks[$tid]) ? $talentRanks[$tid] : 0;
                            $maxRank = 0;
                            for ($r = 9; $r >= 1; $r--) {
                                if (!empty($t['SpellRank_' . $r])) { $maxRank = $r; break; }
                            }
                            $icon    = $t['IconName'] ?? 'inv_misc_questionmark';
                            $gridRow = (int)$t['TierID'] + 1;
                            $gridCol = (int)$t['ColumnIndex'] + 1;
                        ?>
                        <div class="tt-cell<?= $rank > 0 ? ' tt-allocated' : ''; ?>"
                             style="grid-row:<?= $gridRow; ?>;grid-column:<?= $gridCol; ?>">
                            <img src="https://wow.zamimg.com/images/wow/icons/medium/<?= htmlspecialchars($icon); ?>.jpg"
                                 alt="" class="tt-icon"
                                 onerror="this.src='https://wow.zamimg.com/images/wow/icons/medium/inv_misc_questionmark.jpg'">
                            <?php if ($maxRank > 0): ?>
                            <span class="tt-rank<?= ($rank > 0 && $rank >= $maxRank) ? ' tt-rank-max' : ''; ?>">
                                <?= $rank; ?>/<?= $maxRank; ?>
                            </span>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div><!-- /.tt-grid -->
                </div><!-- /.tt-tree -->
                <?php endforeach; ?>
            </div><!-- /.tt-trees -->
        </div><!-- /.tt-spec -->
        <?php endforeach; ?>

    </div><!-- /.pv-talents -->
</div><!-- /.pv-talents-wrap -->
<?php endif; ?>

<?php endforeach; ?>

<script>
(function () {
    'use strict';
    const BASE = '<?= base_url(); ?>';

    /* ── Tooltip DOM ─────────────────────────────────────────── */
    const TIP = document.createElement('div');
    TIP.id = 'ctt';
    document.body.appendChild(TIP);

    /* ── Tables de mapping ───────────────────────────────────── */
    const Q_COLOR = ['#9d9d9d','#ffffff','#1eff00','#0070dd','#a335ee','#ff8000','#e6cc80','#00ccff'];

    // Source : AzerothCore ItemModType enum (SharedDefines.h)
    const STAT_LABEL = {
        0:  'Mana',
        1:  'Santé',
        3:  'Agilité',
        4:  'Force',
        5:  'Intelligence',
        6:  'Esprit',
        7:  'Endurance',
        12: 'Défense',
        13: 'Esquive',
        14: 'Parade',
        15: 'Blocage',
        16: 'Toucher (mêlée)',
        17: 'Toucher (distance)',
        18: 'Toucher (sorts)',
        19: 'Critique (mêlée)',
        20: 'Critique (distance)',
        21: 'Critique (sorts)',
        28: 'Hâte (mêlée)',
        29: 'Hâte (distance)',
        30: 'Hâte (sorts)',
        31: 'Toucher',
        32: 'Critique',
        35: 'Résilience',
        36: 'Hâte',
        37: 'Expertise',
        38: "Puissance d'attaque",
        39: 'PA à distance',
        43: 'Régén. de mana',
        44: "Pénétration d'armure",
        45: 'Puissance des sorts',
        46: 'Régén. santé (5 s)',
        47: 'Pénétration de sorts',
        48: 'Valeur de blocage',
    };

    const TRIGGER_LABEL = { 0:'Utilisation', 1:'Équipement', 2:'Chance' };

    const INV_LABEL = {
        1:'Tête', 2:'Cou', 3:'Épaules', 4:'Chemise', 5:'Torse',
        6:'Ceinture', 7:'Jambes', 8:'Pieds', 9:'Poignets', 10:'Mains',
        11:'Anneau', 12:'Bijou', 13:'Arme à une main', 14:'Hors-main',
        15:'Arme à distance', 17:'Cape', 18:'Arme à deux mains',
        22:'Main droite', 23:'Main gauche', 24:'Hors-main',
        25:'Amulette', 26:'Arme à distance', 28:'Relique',
    };

    const ARMOR_SUB  = { 0:'Divers', 1:'Tissu', 2:'Cuir', 3:'Mailles', 4:'Mailles', 5:'Plaques', 6:'Bouclier' };
    const WEAPON_SUB = {
        0:'Hache', 1:'Hache à deux mains', 2:'Arc', 3:'Fusil',
        4:'Massue', 5:'Massue à deux mains', 7:'Dague',
        8:'Épée', 9:'Épée à deux mains', 11:'Pique',
        14:'Arbalète', 15:'Baguette', 16:'Bâton', 19:'Poing', 20:'Épée courte',
    };

    /* ── Helpers ─────────────────────────────────────────────── */
    function esc(s) {
        return String(s || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    }

    /* ── Construction du HTML tooltip ───────────────────────── */
    function buildTooltip(d, realmItems, gems, enchant) {
        realmItems = realmItems || new Set();
        const gemArr = Array.isArray(gems) ? gems : [];
        enchant = enchant || null;
        const q   = parseInt(d.Quality, 10) || 1;
        const ic  = parseInt(d.item_class, 10);
        const is_ = parseInt(d.item_subclass, 10);
        const iv  = parseInt(d.InventoryType, 10);

        let h = `<div class="ctt-name" style="color:${Q_COLOR[q] || '#fff'}">${esc(d.name)}</div>`;

        if (parseInt(d.ItemLevel, 10) > 0) {
            h += `<div class="ctt-ilvl">Niveau d'objet ${d.ItemLevel}</div>`;
        }

        /* Type / slot */
        let typeL = '', typeR = INV_LABEL[iv] || '';
        if      (ic === 4) { typeL = ARMOR_SUB[is_]  || 'Armure';  typeR = typeR || 'Armure'; }
        else if (ic === 2) { typeL = WEAPON_SUB[is_] || 'Arme'; }
        else if (ic === 0) { typeL = 'Consommable'; }
        if (typeL || typeR) {
            h += `<div class="ctt-typeline"><span>${esc(typeL)}</span><span>${esc(typeR)}</span></div>`;
        }

        /* Armure */
        const armor = parseInt(d.armor, 10);
        if (armor > 0) h += `<div class="ctt-armor">${armor} d'armure</div>`;

        /* Dégâts arme */
        const dmin = parseFloat(d.dmg_min1), dmax = parseFloat(d.dmg_max1);
        if (dmin > 0 || dmax > 0) {
            const spd = ((parseFloat(d.delay) || 2000) / 1000).toFixed(2);
            const dps = ((dmin + dmax) / 2 / parseFloat(spd)).toFixed(1);
            h += `<div class="ctt-typeline"><span>${dmin} – ${dmax} Dégâts</span><span>Vitesse ${spd}</span></div>`;
            h += `<div class="ctt-dps">(${dps} dégâts par seconde)</div>`;
        }

        /* Stats bonus — ordre d'affichage WoW */
        const PRIMARY_STATS = new Set([3, 4, 5, 6, 7]);
        const STAT_ORDER = [
            7, 4, 3, 5, 6,          // Endurance, Force, Agilité, Intelligence, Esprit
            43,                      // Régén. de mana (mp5)
            45, 38, 39, 36, 32, 31, 37, 44, 47,  // Offensif
            35,                      // Résilience
            12, 13, 14, 15, 48,     // Tank
        ];
        const rawStats = [];
        for (let i = 1; i <= 8; i++) {
            const t = parseInt(d['stat_type' + i], 10);
            const v = parseInt(d['stat_value' + i], 10);
            if (t > 0 && v !== 0) rawStats.push({ t, v });
        }
        rawStats.sort(function(a, b) {
            const ia = STAT_ORDER.indexOf(a.t);
            const ib = STAT_ORDER.indexOf(b.t);
            const ra = ia === -1 ? 999 : ia;
            const rb = ib === -1 ? 999 : ib;
            return ra - rb;
        });
        let statsOk = false;
        for (const { t, v } of rawStats) {
            if (!statsOk) { h += '<hr class="ctt-divider">'; statsOk = true; }
            const lbl = STAT_LABEL[t] || ('Stat ' + t);
            const cls = PRIMARY_STATS.has(t) ? 'ctt-stat-primary' : 'ctt-stat';
            h += `<div class="${cls}">${v > 0 ? '+' : ''}${v} ${esc(lbl)}</div>`;
        }

        /* Enchantement permanent */
        if (enchant) {
            if (!statsOk) h += '<hr class="ctt-divider">';
            h += `<div class="ctt-enchant">✦ ${esc(enchant)}</div>`;
        }

        /* Chasses (sockets) */
        const SOCK = {
            1:  { label:'Métachasse',         color:'#c39fff' },
            2:  { label:'Chasse rouge',        color:'#ff4444' },
            4:  { label:'Chasse jaune',        color:'#ffdd00' },
            8:  { label:'Chasse bleue',        color:'#4da6ff' },
            14: { label:'Chasse prismatique',  color:'#e0e0e0' },
        };
        const socketColors = [
            parseInt(d.socketColor_1, 10) || 0,
            parseInt(d.socketColor_2, 10) || 0,
            parseInt(d.socketColor_3, 10) || 0,
        ];
        // Sockets définis par l'item OU révélés par une gemme enchâssée (socket ajouté via NPC custom)
        const numSockets = Math.max(
            socketColors.filter(c => c > 0).length,
            gemArr.filter(g => g != null).length
        );

        if (numSockets > 0) {
            h += '<hr class="ctt-divider">';
            const allFilled = Array.from({length: numSockets}, function(_, i) { return gemArr[i] != null; }).every(Boolean);
            for (let i = 0; i < numSockets; i++) {
                const added   = socketColors[i] === 0; // socket ajouté via NPC
                const sc      = added ? 14 : socketColors[i];
                const s       = added
                    ? { label: 'Chasse ajoutée', color: '#b48aff' }
                    : (SOCK[sc] || { label: 'Chasse', color: '#aaa' });
                const gemName = gemArr[i] != null ? gemArr[i] : null;
                if (gemName) {
                    h += `<div class="ctt-socket ctt-sock-filled${added ? ' ctt-sock-added' : ''}">` +
                         `<span class="ctt-sock-gem">◆</span>` +
                         `<span class="ctt-sock-gem-name${added ? ' ctt-sock-added-gem' : ''}">${esc(gemName)}</span>` +
                         `</div>`;
                } else {
                    h += `<div class="ctt-socket${added ? ' ctt-sock-added' : ''}">` +
                         `<span class="ctt-sock-gem" style="color:${s.color}">◆</span>` +
                         `<span class="ctt-sock-label" style="color:${s.color}">${esc(s.label)}</span>` +
                         `</div>`;
                }
            }
            const bonusText = (d.socket_bonus_desc || '').trim();
            h += `<div class="ctt-sock-bonus${allFilled ? ' ctt-sock-bonus-on' : ''}">` +
                 `Bonus de chasse : <span>${bonusText || '—'}</span></div>`;
        }

        /* Sorts / effets */
        const IDS   = [d.spellid_1,     d.spellid_2,     d.spellid_3,     d.spellid_4,     d.spellid_5];
        const TRGS  = [d.spelltrigger_1, d.spelltrigger_2, d.spelltrigger_3, d.spelltrigger_4, d.spelltrigger_5];
        const NAMES = [d.spell1_name,   d.spell2_name,   d.spell3_name,   d.spell4_name,   d.spell5_name];
        const DESC  = [d.spell1_desc,   d.spell2_desc,   d.spell3_desc,   d.spell4_desc,   d.spell5_desc];

        let spellsOk = false;
        for (let i = 0; i < 5; i++) {
            if (parseInt(IDS[i], 10) <= 0) continue;
            const name = (NAMES[i] || '').trim();
            const desc = (DESC[i] || '').trim();
            if (!name && !desc) continue;
            if (!spellsOk) { h += '<hr class="ctt-divider">'; spellsOk = true; }
            const tl = TRIGGER_LABEL[parseInt(TRGS[i], 10)] ?? 'Équipement';
            let spellText = '';
            if (name && desc) {
                spellText = `<span class="ctt-spell-name">${esc(name)}</span> — ${esc(desc)}`;
            } else {
                spellText = esc(name || desc);
            }
            h += `<div class="ctt-spell"><span class="ctt-trigger">${esc(tl)} :</span> ${spellText}</div>`;
        }

        /* Niveau requis */
        const rl = parseInt(d.RequiredLevel, 10);
        if (rl > 0) h += `<div class="ctt-req">Niveau requis : ${rl}</div>`;
        h += `<div class="ctt-item-id">Item ID : ${d.entry}</div>`;

        /* Bonus de set */
        if (d.set_name && d.set_bonuses && d.set_bonuses.length) {
            h += '<hr class="ctt-divider">';
            const setItemIds    = Array.isArray(d.set_items) ? d.set_items : [];
            const equippedCount = setItemIds.filter(id => realmItems.has(id)).length;
            const totalPieces   = d.set_total || setItemIds.length;
            h += `<div class="ctt-set-name">${esc(d.set_name)} (${equippedCount}/${totalPieces})</div>`;
            for (const b of d.set_bonuses) {
                const active = equippedCount >= b.threshold;
                const text   = (b.desc || b.name || '').trim();
                h += `<div class="ctt-set-bonus${active ? ' ctt-set-on' : ''}">` +
                     `<span class="ctt-set-thr">${b.threshold} pièces : </span>${esc(text)}</div>`;
            }
        }

        return h;
    }

    /* ── Positionnement ──────────────────────────────────────── */
    function posTip(e) {
        const W = window.innerWidth, H = window.innerHeight;
        const TW = TIP.offsetWidth + 20, TH = TIP.offsetHeight + 20;
        let x = e.clientX + 18, y = e.clientY + 18;
        if (x + TW > W) x = e.clientX - TW + 18;
        if (y + TH > H) y = e.clientY - TH;
        TIP.style.left = x + 'px';
        TIP.style.top  = y + 'px';
    }

    /* ── Cache & fetch ───────────────────────────────────────── */
    const cache = new Map();

    async function fetchItem(itemId, realmId) {
        const key = realmId + '_' + itemId;
        if (cache.has(key)) return cache.get(key);
        try {
            const r   = await fetch(BASE + 'armory/item_data/' + realmId + '/' + itemId);
            const d   = await r.json();
            const val = (d && !d.error) ? d : null;
            cache.set(key, val);
            return val;
        } catch (_) {
            cache.set(key, null);
            return null;
        }
    }

    /* ── Événements ──────────────────────────────────────────── */
    document.querySelectorAll('.pv-item-link[data-item]').forEach(el => {
        el.addEventListener('mouseenter', async e => {
            const d = await fetchItem(el.dataset.item, el.dataset.realm);
            if (!d) return;
            const realmId = parseInt(el.dataset.realm, 10);
            const slot    = parseInt(el.dataset.slot,  10);
            const ri      = (window._realmItems    || {})[realmId] || new Set();
            const rg      = (window._realmGems     || {})[realmId] || {};
            const re      = (window._realmEnchants || {})[realmId] || {};
            const gems    = rg[slot] || null;
            const enchant = re[slot] || null;
            TIP.innerHTML     = buildTooltip(d, ri, gems, enchant);
            TIP.style.display = 'block';
            posTip(e);
        });
        el.addEventListener('mousemove',  e  => { if (TIP.style.display !== 'none') posTip(e); });
        el.addEventListener('mouseleave', ()  => { TIP.style.display = 'none'; });
    });
})();

/* ── Talent spec switcher ────────────────────────────────── */
document.querySelectorAll('.tt-spec-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var spec = btn.dataset.spec;
        document.querySelectorAll('.tt-spec-btn').forEach(function(b) { b.classList.remove('active'); });
        btn.classList.add('active');
        document.querySelectorAll('.tt-spec').forEach(function(p) {
            p.classList.toggle('tt-spec-hidden', p.dataset.spec !== spec);
        });
    });
});
</script>
