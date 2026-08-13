<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class armory_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    /* ════════════════════════════════════════════════════
       JOUEUR
    ════════════════════════════════════════════════════ */

    public function getPlayerInfo($MultiRealm, $id)
    {
        return $MultiRealm->select('*')->where('guid', $id)->get('characters');
    }

    /**
     * Récupère TOUT l'équipement d'un joueur en UNE seule requête.
     * Retourne [slot => itemEntry]
     */
    public function getAllEquipment($MultiRealm, $id): array
    {
        $rows = $MultiRealm
            ->select('a.slot, b.itemEntry')
            ->from('character_inventory a')
            ->join('item_instance b', 'a.item = b.guid', 'left')
            ->where('a.guid', $id)
            ->where('a.bag', 0)
            ->where_in('a.slot', [0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,19])
            ->get()
            ->result();

        $map = [];
        foreach ($rows as $row) {
            if ($row->itemEntry) {
                $map[(int) $row->slot] = (int) $row->itemEntry;
            }
        }
        return $map;
    }

    /**
     * Retourne les gemmes enchâssées par slot d'équipement.
     * [slotNum => [desc_gem1|null, desc_gem2|null, desc_gem3|null]]
     * Indices enchantments : socket slot 2→idx 6, slot 3→idx 9, slot 4→idx 12
     */
    public function getEquipmentGems($MultiRealm, $id): array
    {
        $rows = $MultiRealm
            ->select('a.slot, b.enchantments')
            ->from('character_inventory a')
            ->join('item_instance b', 'a.item = b.guid', 'left')
            ->where('a.guid', $id)
            ->where('a.bag', 0)
            ->where_in('a.slot', [0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,19])
            ->get()
            ->result();

        if (empty($rows)) return [];

        $slotGemIds = [];
        $allEnchIds = [];

        foreach ($rows as $row) {
            $slot  = (int)$row->slot;
            $parts = $row->enchantments ? explode(' ', trim($row->enchantments)) : [];
            $gems  = [];
            for ($i = 0; $i < 3; $i++) {
                $idx  = ($i + 2) * 3;            // sock slots 2,3,4 → indices 6,9,12
                $eid  = (int)($parts[$idx] ?? 0);
                $gems[] = $eid;
                if ($eid > 0) $allEnchIds[$eid] = true;
            }
            $slotGemIds[$slot] = $gems;
        }

        // Si aucune gemme nulle part, on retourne tout à null sans requête DB
        if (empty($allEnchIds)) {
            $result = [];
            foreach ($slotGemIds as $slot => $ids) {
                $result[$slot] = [null, null, null];
            }
            return $result;
        }

        $ids     = implode(',', array_map('intval', array_keys($allEnchIds)));
        $nameMap = [];
        $gRows   = $this->db->query(
            "SELECT ID,
                    COALESCE(NULLIF(Name_Lang_koKR,''), NULLIF(Name_Lang_frFR,''), NULLIF(Name_Lang_enUS,'')) AS gem_name
             FROM R1_World.spellitemenchantment_dbc
             WHERE ID IN ($ids)"
        )->result_array();
        foreach ($gRows as $r) {
            $nameMap[(int)$r['ID']] = $r['gem_name'];
        }

        $result = [];
        foreach ($slotGemIds as $slot => $gemIds) {
            $descs = [];
            foreach ($gemIds as $eid) {
                $descs[] = ($eid > 0 && isset($nameMap[$eid])) ? $nameMap[$eid] : null;
            }
            $result[$slot] = $descs;
        }
        return $result;
    }

    /**
     * Retourne l'enchantement permanent par slot d'équipement.
     * [slotNum => desc|null]
     * L'enchantement permanent est au slot 0 (index 0) de item_instance.enchantments.
     */
    public function getEquipmentEnchants($MultiRealm, $id): array
    {
        $rows = $MultiRealm
            ->select('a.slot, b.enchantments')
            ->from('character_inventory a')
            ->join('item_instance b', 'a.item = b.guid', 'left')
            ->where('a.guid', $id)
            ->where('a.bag', 0)
            ->where_in('a.slot', [0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,19])
            ->get()
            ->result();

        if (empty($rows)) return [];

        $slotIds = [];
        $allIds  = [];

        foreach ($rows as $row) {
            $slot  = (int)$row->slot;
            $parts = $row->enchantments ? explode(' ', trim($row->enchantments)) : [];
            $eid   = (int)($parts[0] ?? 0); // slot 0 = enchantement permanent
            $slotIds[$slot] = $eid;
            if ($eid > 0) $allIds[$eid] = true;
        }

        if (empty($allIds)) {
            $result = [];
            foreach ($slotIds as $slot => $_) { $result[$slot] = null; }
            return $result;
        }

        $ids     = implode(',', array_map('intval', array_keys($allIds)));
        $nameMap = [];
        foreach ($this->db->query(
            "SELECT ID,
                    COALESCE(NULLIF(Name_Lang_koKR,''), NULLIF(Name_Lang_frFR,''), NULLIF(Name_Lang_enUS,'')) AS ename
             FROM R1_World.spellitemenchantment_dbc WHERE ID IN ($ids)"
        )->result_array() as $r) {
            $nameMap[(int)$r['ID']] = $r['ename'];
        }

        $result = [];
        foreach ($slotIds as $slot => $eid) {
            $result[$slot] = ($eid > 0 && isset($nameMap[$eid])) ? $nameMap[$eid] : null;
        }
        return $result;
    }

    /**
     * Récupère les noms d'icônes pour une liste d'itemEntry
     * via la jointure item_template → itemdisplayinfo_dbc
     * Retourne [itemEntry => iconName]
     */
    public function getItemIcons(array $itemIds, int $realmid = 1): array
    {
        if (empty($itemIds)) return [];

        $in = implode(',', array_map('intval', $itemIds));

        $sql = "
            SELECT it.entry, LOWER(d.InventoryIcon_1) AS icon
            FROM R{$realmid}_World.item_template it
            JOIN R1_World.itemdisplayinfo_dbc d ON it.displayid = d.ID
            WHERE it.entry IN ({$in})
              AND d.InventoryIcon_1 IS NOT NULL
              AND d.InventoryIcon_1 != ''
        ";

        $rows = $this->db->query($sql)->result();
        $map  = [];
        foreach ($rows as $row) {
            $map[(int) $row->entry] = $row->icon;
        }
        return $map;
    }

    /**
     * Récupère icône, nom frFR, qualité ET displayid en une seule requête.
     * Le displayid est nécessaire pour wow-model-viewer (résolution WotLK → retail).
     *
     * Retourne [itemEntry => ['icon' => '...', 'name' => '...', 'quality' => N, 'displayid' => N]]
     */
    public function getItemData(array $itemIds, int $realmid = 1): array
    {
        if (empty($itemIds)) return [];

        $in = implode(',', array_map('intval', $itemIds));

        $sql = "
            SELECT
                it.entry,
                it.Quality,
                it.displayid,
                LOWER(d.InventoryIcon_1) AS icon,
                COALESCE(NULLIF(loc.Name, ''), it.name) AS name
            FROM R{$realmid}_World.item_template it
            LEFT JOIN R1_World.itemdisplayinfo_dbc d ON it.displayid = d.ID
            LEFT JOIN R{$realmid}_World.item_template_locale loc
                ON loc.ID = it.entry AND loc.locale = 'frFR'
            WHERE it.entry IN ({$in})
        ";

        $rows = $this->db->query($sql)->result();
        $map  = [];
        foreach ($rows as $row) {
            $map[(int) $row->entry] = [
                'icon'      => $row->icon      ?? null,
                'name'      => $row->name      ?? null,
                'quality'   => (int) ($row->Quality   ?? 1),
                'displayid' => (int) ($row->displayid ?? 0),
            ];
        }
        return $map;
    }

    /**
     * Données complètes pour le tooltip custom (stats + sorts + descriptions spell_dbc).
     * Retourne null si l'item n'existe pas.
     */
    public function getItemTooltipData(int $entry, int $realmid = 1): ?array
    {
        $sql = "
            SELECT
                it.entry,
                COALESCE(NULLIF(loc.Name, ''), it.name)   AS name,
                it.Quality,
                it.ItemLevel,
                it.RequiredLevel,
                it.class                                   AS item_class,
                it.subclass                                AS item_subclass,
                it.InventoryType,
                it.armor,
                it.dmg_min1, it.dmg_max1, it.dmg_type1, it.delay,
                it.stat_type1,  it.stat_value1,
                it.stat_type2,  it.stat_value2,
                it.stat_type3,  it.stat_value3,
                it.stat_type4,  it.stat_value4,
                it.stat_type5,  it.stat_value5,
                it.stat_type6,  it.stat_value6,
                it.stat_type7,  it.stat_value7,
                it.stat_type8,  it.stat_value8,
                it.itemset,
                it.socketColor_1, it.socketColor_2, it.socketColor_3, it.socketBonus,
                it.spellid_1, it.spelltrigger_1, it.spellcharges_1,
                it.spellid_2, it.spelltrigger_2, it.spellcharges_2,
                it.spellid_3, it.spelltrigger_3, it.spellcharges_3,
                it.spellid_4, it.spelltrigger_4, it.spellcharges_4,
                it.spellid_5, it.spelltrigger_5, it.spellcharges_5,
                NULLIF(sd1.Name_Lang_koKR,        '') AS spell1_name,
                NULLIF(sd1.Description_Lang_koKR, '') AS spell1_desc,
                sd1.EffectBasePoints_1 AS sp1_bp1, sd1.EffectBasePoints_2 AS sp1_bp2, sd1.EffectBasePoints_3 AS sp1_bp3,
                sd1.EffectDieSides_1   AS sp1_ds1, sd1.EffectDieSides_2   AS sp1_ds2, sd1.EffectDieSides_3   AS sp1_ds3,
                sd1.EffectChainTargets_1 AS sp1_ct1, sd1.MaxTargets AS sp1_mxt, sd1.DurationIndex AS sp1_dix,
                NULLIF(sd2.Name_Lang_koKR,        '') AS spell2_name,
                NULLIF(sd2.Description_Lang_koKR, '') AS spell2_desc,
                sd2.EffectBasePoints_1 AS sp2_bp1, sd2.EffectBasePoints_2 AS sp2_bp2, sd2.EffectBasePoints_3 AS sp2_bp3,
                sd2.EffectDieSides_1   AS sp2_ds1, sd2.EffectDieSides_2   AS sp2_ds2, sd2.EffectDieSides_3   AS sp2_ds3,
                sd2.EffectChainTargets_1 AS sp2_ct1, sd2.MaxTargets AS sp2_mxt, sd2.DurationIndex AS sp2_dix,
                NULLIF(sd3.Name_Lang_koKR,        '') AS spell3_name,
                NULLIF(sd3.Description_Lang_koKR, '') AS spell3_desc,
                sd3.EffectBasePoints_1 AS sp3_bp1, sd3.EffectBasePoints_2 AS sp3_bp2, sd3.EffectBasePoints_3 AS sp3_bp3,
                sd3.EffectDieSides_1   AS sp3_ds1, sd3.EffectDieSides_2   AS sp3_ds2, sd3.EffectDieSides_3   AS sp3_ds3,
                sd3.EffectChainTargets_1 AS sp3_ct1, sd3.MaxTargets AS sp3_mxt, sd3.DurationIndex AS sp3_dix,
                NULLIF(sd4.Name_Lang_koKR,        '') AS spell4_name,
                NULLIF(sd4.Description_Lang_koKR, '') AS spell4_desc,
                sd4.EffectBasePoints_1 AS sp4_bp1, sd4.EffectBasePoints_2 AS sp4_bp2, sd4.EffectBasePoints_3 AS sp4_bp3,
                sd4.EffectDieSides_1   AS sp4_ds1, sd4.EffectDieSides_2   AS sp4_ds2, sd4.EffectDieSides_3   AS sp4_ds3,
                sd4.EffectChainTargets_1 AS sp4_ct1, sd4.MaxTargets AS sp4_mxt, sd4.DurationIndex AS sp4_dix,
                NULLIF(sd5.Name_Lang_koKR,        '') AS spell5_name,
                NULLIF(sd5.Description_Lang_koKR, '') AS spell5_desc,
                sd5.EffectBasePoints_1 AS sp5_bp1, sd5.EffectBasePoints_2 AS sp5_bp2, sd5.EffectBasePoints_3 AS sp5_bp3,
                sd5.EffectDieSides_1   AS sp5_ds1, sd5.EffectDieSides_2   AS sp5_ds2, sd5.EffectDieSides_3   AS sp5_ds3,
                sd5.EffectChainTargets_1 AS sp5_ct1, sd5.MaxTargets AS sp5_mxt, sd5.DurationIndex AS sp5_dix
            FROM R{$realmid}_World.item_template it
            LEFT JOIN R{$realmid}_World.item_template_locale loc
                ON  loc.ID = it.entry AND loc.locale = 'frFR'
            LEFT JOIN R1_World.spell_dbc sd1 ON sd1.ID = it.spellid_1 AND it.spellid_1 > 0
            LEFT JOIN R1_World.spell_dbc sd2 ON sd2.ID = it.spellid_2 AND it.spellid_2 > 0
            LEFT JOIN R1_World.spell_dbc sd3 ON sd3.ID = it.spellid_3 AND it.spellid_3 > 0
            LEFT JOIN R1_World.spell_dbc sd4 ON sd4.ID = it.spellid_4 AND it.spellid_4 > 0
            LEFT JOIN R1_World.spell_dbc sd5 ON sd5.ID = it.spellid_5 AND it.spellid_5 > 0
            WHERE it.entry = ?
            LIMIT 1
        ";
        $row = $this->db->query($sql, [$entry])->row_array();
        if (!$row) return null;

        for ($n = 1; $n <= 5; $n++) {
            $dk = "spell{$n}_desc";
            if (!empty($row[$dk])) {
                $row[$dk] = $this->resolveSpellDesc($row[$dk], $row, $n);
            }
            foreach (['bp1','bp2','bp3','ds1','ds2','ds3','ct1','mxt','dix'] as $c) {
                unset($row["sp{$n}_{$c}"]);
            }
        }

        $socketBonus = (int)($row['socketBonus'] ?? 0);
        if ($socketBonus > 0) {
            $row['socket_bonus_desc'] = $this->getSocketBonusDesc($socketBonus);
        }

        $setId = (int)($row['itemset'] ?? 0);
        if ($setId > 0) {
            $setData = $this->getSetBonuses($setId, $realmid);
            if ($setData) {
                $row['set_name']    = $setData['set_name'];
                $row['set_total']   = $setData['set_total'];
                $row['set_items']   = $setData['set_items'];
                $row['set_bonuses'] = $setData['set_bonuses'];
            }
        }

        return $row;
    }

    private function getSocketBonusDesc(int $enchantId): string
    {
        $row = $this->db->query(
            "SELECT NULLIF(Name_Lang_koKR,'') AS koKR,
                    NULLIF(Name_Lang_frFR,'') AS frFR,
                    NULLIF(Name_Lang_enUS,'') AS enUS
             FROM R1_World.spellitemenchantment_dbc WHERE ID = ? LIMIT 1",
            [$enchantId]
        )->row_array();
        if (!$row) return '';
        return $row['koKR'] ?? $row['frFR'] ?? $row['enUS'] ?? '';
    }

    public function getSetBonuses(int $setId, int $realmid = 1): ?array
    {
        $sql = "
            SELECT
                COALESCE(NULLIF(Name_Lang_frFR,''), NULLIF(Name_Lang_koKR,''), NULLIF(Name_Lang_enUS,'')) AS set_name,
                (ItemID_1>0)+(ItemID_2>0)+(ItemID_3>0)+(ItemID_4>0)+(ItemID_5>0)+
                (ItemID_6>0)+(ItemID_7>0)+(ItemID_8>0)+(ItemID_9>0)+(ItemID_10>0)+
                (ItemID_11>0)+(ItemID_12>0)+(ItemID_13>0)+(ItemID_14>0)+(ItemID_15>0)+
                (ItemID_16>0)+(ItemID_17>0) AS set_total,
                SetSpellID_1, SetThreshold_1, SetSpellID_2, SetThreshold_2,
                SetSpellID_3, SetThreshold_3, SetSpellID_4, SetThreshold_4,
                SetSpellID_5, SetThreshold_5, SetSpellID_6, SetThreshold_6,
                SetSpellID_7, SetThreshold_7, SetSpellID_8, SetThreshold_8
            FROM R1_World.itemset_dbc WHERE ID = ? LIMIT 1
        ";
        $set = $this->db->query($sql, [$setId])->row_array();
        if (!$set) return null;

        // Toutes les entries du set sur ce realm (inclut variantes) — pour matcher l'inventaire
        $rows  = $this->db->query(
            "SELECT entry FROM R{$realmid}_World.item_template WHERE itemset = ? AND entry > 0 ORDER BY entry",
            [$setId]
        )->result_array();
        $items = array_map('intval', array_column($rows, 'entry'));

        $bonuses = [];
        for ($i = 1; $i <= 8; $i++) {
            $spellId   = (int)($set["SetSpellID_{$i}"]  ?? 0);
            $threshold = (int)($set["SetThreshold_{$i}"] ?? 0);
            if ($spellId <= 0 || $threshold <= 0) continue;

            $sp = $this->db->query(
                "SELECT NULLIF(Name_Lang_koKR,'') AS sname,
                        NULLIF(Description_Lang_koKR,'') AS sdesc,
                        EffectBasePoints_1, EffectBasePoints_2, EffectBasePoints_3,
                        EffectDieSides_1,   EffectDieSides_2,   EffectDieSides_3,
                        EffectChainTargets_1, MaxTargets, DurationIndex
                 FROM R1_World.spell_dbc WHERE ID = ? LIMIT 1",
                [$spellId]
            )->row_array();
            if (!$sp) continue;

            $desc = $sp['sdesc'] ?? '';
            if ($desc) {
                $fakeRow = [
                    'sp1_bp1' => $sp['EffectBasePoints_1']    ?? -1,
                    'sp1_bp2' => $sp['EffectBasePoints_2']    ?? -1,
                    'sp1_bp3' => $sp['EffectBasePoints_3']    ?? -1,
                    'sp1_ds1' => $sp['EffectDieSides_1']      ?? 1,
                    'sp1_ds2' => $sp['EffectDieSides_2']      ?? 1,
                    'sp1_ds3' => $sp['EffectDieSides_3']      ?? 1,
                    'sp1_ct1' => $sp['EffectChainTargets_1']  ?? 0,
                    'sp1_mxt' => $sp['MaxTargets']             ?? 0,
                    'sp1_dix' => $sp['DurationIndex']          ?? 0,
                ];
                $desc = $this->resolveSpellDesc($desc, $fakeRow, 1);
            }

            $bonuses[] = [
                'threshold' => $threshold,
                'name'      => $sp['sname'] ?? '',
                'desc'      => $desc,
            ];
        }

        usort($bonuses, function ($a, $b) { return $a['threshold'] - $b['threshold']; });

        return [
            'set_name'    => $set['set_name'] ?? '',
            'set_total'   => (int)($set['set_total'] ?? count($items)),
            'set_items'   => $items,
            'set_bonuses' => $bonuses,
        ];
    }

    private function resolveSpellDesc(string $desc, array $row, int $n): string
    {
        $p = "sp{$n}_";

        $bp = [
            1 => (int)($row[$p.'bp1'] ?? -1),
            2 => (int)($row[$p.'bp2'] ?? -1),
            3 => (int)($row[$p.'bp3'] ?? -1),
        ];
        $ds = [
            1 => (int)($row[$p.'ds1'] ?? 1),
            2 => (int)($row[$p.'ds2'] ?? 1),
            3 => (int)($row[$p.'ds3'] ?? 1),
        ];
        $ct1  = (int)($row[$p.'ct1'] ?? 0);
        $mxt  = (int)($row[$p.'mxt'] ?? 0);
        $dix  = (int)($row[$p.'dix'] ?? 0);

        // $sN / $mN / $eN → base value (EffectBasePoints + 1)
        $desc = preg_replace_callback('/\$([smeSME])([123])/i', function ($m) use ($bp, $ds) {
            $i  = (int)$m[2];
            $lo = $bp[$i] + 1;
            $hi = $bp[$i] + ($ds[$i] ?: 1);
            return (strtoupper($m[1]) === 'M') ? $hi : $lo;
        }, $desc);

        // $x1 → chain targets
        $desc = preg_replace('/\$x1/i', $ct1 ?: '', $desc);

        // $n → max targets
        if ($mxt > 0) $desc = preg_replace('/\$n\b/i', $mxt, $desc);

        // Cross-spell refs: $12345sN → other spell's EffectBasePoints_N + 1
        $db = $this->db;
        $desc = preg_replace_callback('/\$(\d{2,})[sSmMeE](\d)/i', function ($m) use ($db) {
            $sid = (int)$m[1];
            $eff = (int)$m[2];
            $col = "EffectBasePoints_{$eff}";
            $r   = $db->query("SELECT `{$col}` FROM R1_World.spell_dbc WHERE ID = ? LIMIT 1", [$sid])->row_array();
            return isset($r[$col]) ? ((int)$r[$col] + 1) : '';
        }, $desc);

        // $d → duration from hardcoded WotLK 3.3.5a SpellDuration.dbc
        if (strpos($desc, '$d') !== false || strpos($desc, '$D') !== false) {
            $durStr = $this->formatSpellDuration($dix);
            $desc   = preg_replace('/\$[dD]\b/', $durStr, $desc);
        }

        // Strip remaining unresolvable codes
        $desc = preg_replace('/\$[a-zA-Z0-9]+/', '', $desc);

        return trim($desc);
    }

    private function formatSpellDuration(int $durationIndex): string
    {
        static $DUR = [
            1=>1,2=>2,3=>3,4=>4,5=>5,6=>6,7=>7,8=>8,9=>9,10=>10,
            11=>11,12=>12,13=>13,14=>14,15=>15,16=>16,17=>17,18=>18,19=>19,20=>20,
            21=>25,22=>30,23=>45,24=>60,25=>90,26=>120,27=>180,28=>240,29=>300,
            30=>600,31=>900,32=>1200,33=>1800,34=>3600,35=>7200,36=>10800,
            37=>14400,38=>21600,39=>28800,40=>43200,41=>86400,
            51=>3,52=>4,53=>5,54=>6,55=>7,56=>8,57=>10,58=>12,59=>15,
            60=>20,61=>25,62=>30,63=>35,64=>45,65=>60,66=>90,67=>120,
        ];
        $s = $DUR[$durationIndex] ?? 0;
        if ($s <= 0)  return '';
        if ($s < 60)  return $s . ' s';
        if ($s < 3600 && $s % 60 === 0) return ($s / 60) . ' min';
        if ($s % 3600 === 0)            return ($s / 3600) . ' h';
        return $s . ' s';
    }

    public function getLogros($MultiRealm, $id)
    {
        return $MultiRealm->select('guid')->where('guid', $id)->get('character_achievement')->num_rows();
    }

    /* ════════════════════════════════════════════════════
       TALENTS
    ════════════════════════════════════════════════════ */

    public function getCharacterTalentSpells($MultiRealm, $id): array
    {
        $result = $MultiRealm
            ->select('spell, specMask')
            ->from('character_talent')
            ->where('guid', (int)$id)
            ->get();

        $bySpec = [0 => [], 1 => []];
        if (!$result) return $bySpec;

        foreach ($result->result_array() as $row) {
            $mask = (int)$row['specMask'];
            $spellId = (int)$row['spell'];
            if ($mask & 1) $bySpec[0][$spellId] = true;
            if ($mask & 2) $bySpec[1][$spellId] = true;
        }
        return $bySpec;
    }

    public function getTalentTabsForClass(int $classId, int $realmid = 1): array
    {
        $mask = 1 << ($classId - 1);
        return $this->db->query(
            "SELECT ID, COALESCE(NULLIF(Name_Lang_frFR,''), Name_Lang_koKR, Name_Lang_enUS) AS name,
                    BackgroundFile, OrderIndex
             FROM R{$realmid}_World.talenttab_dbc
             WHERE ClassMask = {$mask}
             ORDER BY OrderIndex"
        )->result_array();
    }

    public function getTalentsForClass(int $classId, int $realmid = 1): array
    {
        $mask = 1 << ($classId - 1);
        return $this->db->query(
            "SELECT t.ID, t.TabID, t.TierID, t.ColumnIndex,
                    t.SpellRank_1, t.SpellRank_2, t.SpellRank_3, t.SpellRank_4, t.SpellRank_5,
                    t.SpellRank_6, t.SpellRank_7, t.SpellRank_8, t.SpellRank_9,
                    t.PrereqTalent_1, t.PrereqTalent_2, t.PrereqTalent_3, t.PrereqRank_1,
                    COALESCE(i.IconName, 'inv_misc_questionmark') AS IconName
             FROM R{$realmid}_World.talent_dbc t
             LEFT JOIN R{$realmid}_World.talent_icon i ON i.TalentID = t.ID
             WHERE t.TabID IN (
                 SELECT ID FROM R{$realmid}_World.talenttab_dbc WHERE ClassMask = {$mask}
             )
             ORDER BY t.TabID, t.TierID, t.ColumnIndex"
        )->result_array();
    }

    public function getTotalStats(array $itemIds, int $realmid = 1): array
    {
        if (empty($itemIds)) return [];
        $in    = implode(',', array_map('intval', $itemIds));
        $parts = [];
        for ($i = 1; $i <= 8; $i++) {
            $parts[] = "SELECT stat_type{$i} AS t, stat_value{$i} AS v
                        FROM R{$realmid}_World.item_template
                        WHERE entry IN ($in) AND stat_type{$i} > 0 AND stat_value{$i} != 0";
        }
        $rows = $this->db->query(
            "SELECT t, SUM(v) AS total FROM (" . implode(' UNION ALL ', $parts) . ") sub GROUP BY t"
        )->result_array();
        $map = [];
        foreach ($rows as $row) {
            $map[(int)$row['t']] = (int)$row['total'];
        }
        return $map;
    }

    public function getPlayTime($MultiRealm, $id): int
    {
        $row = $MultiRealm->select('totaltime')->where('guid', $id)->get('characters')->row_array();
        return (int)($row['totaltime'] ?? 0);
    }

    public function getAverageIlvl(array $itemIds, int $realmid = 1): float
    {
        $ids = implode(',', array_map('intval', array_filter($itemIds)));
        if (!$ids) return 0.0;
        $r = $this->db->query(
            "SELECT ROUND(AVG(ItemLevel), 0) AS avg_ilvl
             FROM R{$realmid}_World.item_template
             WHERE entry IN ($ids) AND ItemLevel > 0"
        )->row_array();
        return (float)($r['avg_ilvl'] ?? 0);
    }

    public function getReputation($MultiRealm, $id): array
    {
        $rows = $MultiRealm
            ->select('faction, standing')
            ->from('character_reputation')
            ->where('guid', $id)
            ->where('standing >=', 3000)
            ->order_by('standing', 'DESC')
            ->get()
            ->result_array();

        if (empty($rows)) return [];

        $ids     = implode(',', array_map('intval', array_column($rows, 'faction')));
        $nameMap = [];
        foreach ($this->db->query(
            "SELECT ID, COALESCE(NULLIF(Name_Lang_koKR,''), NULLIF(Name_Lang_enUS,'')) AS fname
             FROM R1_World.faction_dbc WHERE ID IN ($ids)"
        )->result_array() as $r) {
            $nameMap[(int)$r['ID']] = $r['fname'];
        }

        $result = [];
        foreach ($rows as $row) {
            $name = $nameMap[(int)$row['faction']] ?? null;
            if (!$name) continue;
            $s = (int)$row['standing'];
            if      ($s >= 42000) $rank = 'Exalté';
            elseif  ($s >= 21000) $rank = 'Révéré';
            elseif  ($s >= 9000)  $rank = 'Honoré';
            else                  $rank = 'Amical';
            $result[] = ['name' => $name, 'standing' => $s, 'rank' => $rank];
        }
        return $result;
    }

    /* ════════════════════════════════════════════════════
       RECHERCHE
    ════════════════════════════════════════════════════ */

    public function searchchar($MultiRealm, $search)
    {
        return $MultiRealm
            ->select('guid, name, race, class, level')
            ->like('LOWER(name)', strtolower($search))
            ->order_by('level', 'DESC')
            ->limit(50)
            ->get('characters');
    }

    public function searchguild($MultiRealm, $search)
    {
        return $MultiRealm
            ->select('guildid, name, motd')
            ->like('LOWER(name)', strtolower($search))
            ->limit(50)
            ->get('guild');
    }

    /* ════════════════════════════════════════════════════
       GUILDE
    ════════════════════════════════════════════════════ */

    public function getGuildName($MultiRealm, int $charGuid): ?string
    {
        $row = $MultiRealm
            ->select('g.name')
            ->from('guild_member gm')
            ->join('guild g', 'g.guildid = gm.guildid', 'left')
            ->where('gm.guid', $charGuid)
            ->limit(1)
            ->get()
            ->row_array();
        return (!empty($row['name'])) ? $row['name'] : null;
    }

    public function getGuildInfo($MultiRealm, $guildid)
    {
        return $MultiRealm->select('*')->where('guildid', $guildid)->get('guild');
    }

    public function getGuildMembers($MultiRealm, $guildid)
    {
        return $MultiRealm
            ->select('a.guid, a.name, a.race, a.class, a.level, b.guildid')
            ->from('characters a')
            ->join('guild_member b', 'a.guid = b.guid', 'left')
            ->where('b.guildid', $guildid)
            ->order_by('a.level', 'DESC')
            ->get();
    }
}
