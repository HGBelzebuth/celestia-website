<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Vote_model — Celestia-WoW
 * ══════════════════════════════════════════════════════════
 * OPTIMISATIONS :
 * - $_votesMap : cache des lignes `votes` indexées par id → O(1) au lieu d'1 SELECT par appel
 * - getVoteById() : point d'entrée unique pour accéder à une ligne votes
 * - getVotePoints() / getVoteTime() / getVoteUrl() / getVoteApiToken() : utilisent le cache
 * - creditVotePoints() : 1 seul SELECT sur `votes` (via cache) + UPDATE atomique dp+points
 * - getCredits() : 1 SELECT, retourne int directement
 * ══════════════════════════════════════════════════════════
 */
class Vote_model extends CI_Model
{
    /** @var array Cache des lignes `votes` indexées par id — O(1) en lecture */
    private $_votesMap = [];

    public function __construct()
    {
        parent::__construct();
    }

    /* ════════════════════════════════════════════════════
       CACHE VOTES — O(1)
    ════════════════════════════════════════════════════ */

    private function getVoteById(int $id): ?object
    {
        if (empty($this->_votesMap)) {
            $rows = $this->db->get('votes')->result();
            foreach ($rows as $row) {
                $this->_votesMap[(int) $row->id] = $row;
            }
        }
        return $this->_votesMap[$id] ?? null;
    }

    /* ════════════════════════════════════════════════════
       GETTERS DE BASE
    ════════════════════════════════════════════════════ */

    public function getVotes(): array
    {
        return $this->db->where('active', 1)->get('votes')->result();
    }

    public function getVotePoints(int $id)
    {
        $row = $this->getVoteById($id);
        return $row ? $row->points : 0;
    }

    public function getVoteTime(int $id)
    {
        $row = $this->getVoteById($id);
        return $row ? $row->time : 0;
    }

    public function getVoteUrl(int $id): string
    {
        $row = $this->getVoteById($id);
        return $row ? (string) $row->url : '';
    }

    public function getVoteApiToken(int $id): string
    {
        $row = $this->getVoteById($id);
        return $row ? (string) $row->api_token : '';
    }

    public function getVoteLog(int $id, int $userid)
    {
        return $this->db
            ->where('idaccount', $userid)
            ->where('idvote', $id)
            ->limit(1)
            ->order_by('id', 'DESC')
            ->get('votes_logs');
    }

    public function getTimeLogExpired(int $id, int $userid)
    {
        return $this->db
            ->where('idaccount', $userid)
            ->where('idvote', $id)
            ->limit(1)
            ->order_by('id', 'DESC')
            ->get('votes_logs')
            ->row('expired_at');
    }

    public function getCredits(int $userid): int
    {
        $row = $this->db->select('vp')->where('id', $userid)->limit(1)->get('users')->row();
        return $row ? (int) $row->vp : 0;
    }

    /* ════════════════════════════════════════════════════
       PERSONNAGE DE RÉCOMPENSE
    ════════════════════════════════════════════════════ */

    public function getRewardChar(int $userid): ?object
    {
        return $this->db
            ->where('userid', $userid)
            ->limit(1)
            ->get('vote_reward_char')
            ->row();
    }

    public function saveRewardChar(int $userid, int $realmid, string $charname): string
    {
        $multirealm = $this->wowrealm->getRealmConnectionData($realmid);
        if (!$multirealm) return 'error';

        $charRow = $multirealm
            ->select('name')
            ->where('account', $userid)
            ->where('name', $charname)
            ->limit(1)
            ->get('characters')
            ->row();

        if (!$charRow) return 'invalid_char';

        $exists = $this->db->where('userid', $userid)->limit(1)->get('vote_reward_char')->row();

        if ($exists) {
            $this->db->where('userid', $userid)->update('vote_reward_char', [
                'realm_id' => $realmid,
                'charname' => $charRow->name,
            ]);
        } else {
            $this->db->insert('vote_reward_char', [
                'userid'   => $userid,
                'realm_id' => $realmid,
                'charname' => $charRow->name,
            ]);
        }

        return 'ok';
    }

    /**
     * Retourne la liste des personnages avec les URL exactes des icônes générées par PHP
     */
    public function getCharacterList(int $userid, int $realmid): array
    {
        $multirealm = $this->wowrealm->getRealmConnectionData($realmid);
        if (!$multirealm) return [];

        $rows = $multirealm
            ->select('name, level, class, race, gender')
            ->where('account', $userid)
            ->order_by('level', 'DESC')
            ->get('characters')
            ->result_array();

        if ($rows) {
            foreach ($rows as &$row) {
                // Utilisation des fonctions natives de ton CMS pour garantir les bonnes URL d'icônes
                $row['race_icon']  = base_url('assets/images/races/' . $this->wowgeneral->getRaceIcon($row['race']));
                $row['class_icon'] = base_url('assets/images/class/' . $this->wowgeneral->getClassIcon($row['class']));
                $row['class_name'] = $this->wowgeneral->getClassName($row['class']);
            }
        }

        return $rows ?: [];
    }

    /* ════════════════════════════════════════════════════
       PENDING VOTES
    ════════════════════════════════════════════════════ */

    public function hasPendingVote(int $userid, int $voteid): ?object
    {
        $limit = $this->wowgeneral->getTimestamp() - 1800;
        return $this->db
            ->where('idaccount', $userid)
            ->where('idvote', $voteid)
            ->where('created_at >', $limit)
            ->get('votes_pending')
            ->row();
    }

    public function addPendingVote(int $userid, int $voteid): void
    {
        $limit = $this->wowgeneral->getTimestamp() - 1800;
        $this->db->where('created_at <', $limit)->delete('votes_pending');
        $this->db->insert('votes_pending', [
            'idaccount'  => $userid,
            'idvote'     => $voteid,
            'created_at' => $this->wowgeneral->getTimestamp(),
        ]);
    }

    public function removePendingVote(int $userid, int $voteid): void
    {
        $this->db
            ->where('idaccount', $userid)
            ->where('idvote', $voteid)
            ->delete('votes_pending');
    }

    public function isDoubleWeek(): bool
    {
        return (int) date('j') <= 7;
    }

    /* ════════════════════════════════════════════════════
       CRÉDIT VP
    ════════════════════════════════════════════════════ */

    public function creditVotePoints(int $userid, int $voteid): bool
    {
        $vote = $this->getVoteById($voteid);
        if (!$vote) return false;

        $points = (int) $vote->points;
        if ($this->isDoubleWeek()) $points *= 2;

        $votetime   = (int) $vote->time;
        $mytime     = $this->wowgeneral->getTimestamp();
        $expired_at = (new DateTime())->add(new DateInterval('PT' . $votetime . 'M'))->getTimestamp();

        $exists = $this->db->select('id')->where('id', $userid)->limit(1)->get('users')->row();
        if (!$exists) return false;

        $this->db->set('vp', 'vp + ' . $points, false)
                 ->where('id', $userid)
                 ->update('users');

        $this->db->insert('votes_logs', [
            'idaccount'  => $userid,
            'idvote'     => $voteid,
            'lasttime'   => $mytime,
            'expired_at' => $expired_at,
            'points'     => $points,
            'ip'         => $this->input->ip_address(),
        ]);

        return true;
    }

    /* ════════════════════════════════════════════════════
       MÉTHODES BULK
    ════════════════════════════════════════════════════ */

    public function getTimeLogExpiredBulk(array $voteIds, int $userid): array
    {
        if (empty($voteIds)) return [];

        $ids = implode(',', array_map('intval', $voteIds));

        $sql = "
            SELECT vl.idvote, vl.expired_at
            FROM votes_logs vl
            INNER JOIN (
                SELECT idvote, MAX(id) AS max_id
                FROM votes_logs
                WHERE idaccount = ?
                  AND idvote IN ({$ids})
                GROUP BY idvote
            ) latest ON vl.id = latest.max_id
        ";

        $rows = $this->db->query($sql, [$userid])->result();

        $map = array_fill_keys($voteIds, null);
        foreach ($rows as $row) {
            $map[(int) $row->idvote] = $row->expired_at;
        }
        return $map;
    }

    public function hasPendingVoteBulk(int $userid, array $voteIds): array
    {
        if (empty($voteIds)) return [];

        $limit = $this->wowgeneral->getTimestamp() - 1800;
        $ids   = implode(',', array_map('intval', $voteIds));

        $sql = "
            SELECT idvote
            FROM votes_pending
            WHERE idaccount = ?
              AND created_at > ?
              AND idvote IN ({$ids})
        ";

        $rows = $this->db->query($sql, [$userid, $limit])->result();

        $map = array_fill_keys($voteIds, false);
        foreach ($rows as $row) {
            $map[(int) $row->idvote] = true;
        }
        return $map;
    }

    public function getVoteUrlBulk(array $voteIds): array
    {
        $map = [];
        foreach ($voteIds as $id) {
            $id       = (int) $id;
            $row      = $this->getVoteById($id);
            $map[$id] = $row ? (string) $row->url : '';
        }
        return $map;
    }

    /* ════════════════════════════════════════════════════
       VOTE NOW
    ════════════════════════════════════════════════════ */

    public function voteNow(int $id): void
    {
        $userid = (int) $this->session->userdata('wow_sess_id');
        if (!$userid) {
            echo json_encode(['status' => 'error', 'message' => 'Session expirée.']);
            return;
        }

        $url = $this->getVoteUrl($id);
        if (!preg_match('~^(?:f|ht)tps?://~i', $url)) {
            $url = 'http://' . $url;
        }

        header('Content-Type: application/json');

        $expired = $this->getVoteLog($id, $userid)->row('expired_at');

        if ($this->wowgeneral->getTimestamp() >= $expired) {
            $vote     = $this->getVoteById($id);
            $api_type = $vote ? $vote->api_type : null;

            if (!empty($api_type)) {
                if ($this->hasPendingVote($userid, $id)) {
                    echo json_encode([
                        'status'  => 'pending',
                        'message' => 'Un vote est déjà en cours de vérification pour ce site. Tes VP seront crédités dans quelques instants.',
                    ]);
                    return;
                }

                if ($api_type === 'top100arena') {
                    $url = rtrim($url, '/') . '?incentive=' . $userid;
                }

                $this->addPendingVote($userid, $id);
                echo json_encode(['status' => 'pending_created', 'vote_url' => $url]);
            } else {
                $this->creditVotePoints($userid, $id);
                echo json_encode(['status' => 'credited', 'vote_url' => $url]);
            }
        } else {
            echo json_encode([
                'status'  => 'cooldown',
                'message' => 'Tu as déjà voté sur ce site récemment. Reviens plus tard !',
            ]);
        }
    }

    public function processTop100ArenaCallback(int $userid): string
    {
        $exists = $this->db->where('id', $userid)->limit(1)->get('users')->row();
        if (!$exists) {
            log_message('error', 'Top100Arena callback: userid introuvable: ' . $userid);
            return 'user_not_found';
        }

        $voteRow = $this->db->where('api_type', 'top100arena')->limit(1)->get('votes')->row();
        if (!$voteRow) {
            log_message('error', 'Top100Arena callback: aucun vote top100arena configuré en BDD');
            return 'vote_not_found';
        }
        $voteid = (int) $voteRow->id;

        if (!$this->hasPendingVote($userid, $voteid)) {
            log_message('error', 'Top100Arena callback: pas de pending pour userid=' . $userid);
            return 'no_pending';
        }

        $already = $this->db
            ->where('idaccount', $userid)
            ->where('idvote', $voteid)
            ->where('expired_at >', $this->wowgeneral->getTimestamp())
            ->get('votes_logs')->row();

        if ($already) {
            $this->removePendingVote($userid, $voteid);
            log_message('info', 'Top100Arena callback: déjà crédité pour userid=' . $userid);
            return 'already_credited';
        }

        $result = $this->creditVotePoints($userid, $voteid);
        if (!$result) {
            log_message('error', 'Top100Arena callback: échec creditVotePoints pour userid=' . $userid);
            return 'credit_failed';
        }

        $this->removePendingVote($userid, $voteid);
        log_message('info', 'Top100Arena callback: VP crédités pour userid=' . $userid);
        return 'ok';
    }

    public function verifyVote(int $id): array
    {
        $userid = (int) $this->session->userdata('wow_sess_id');
        if (!$userid) return ['status' => 'error', 'message' => 'Session expirée.'];

        $vote     = $this->getVoteById($id);
        $api_type = $vote ? $vote->api_type : null;

        if ($api_type === 'top100arena')    return $this->verifyTop100Arena($userid, $id);
        if ($api_type === 'serveur_prive')  return $this->verifyServeurPrive($userid, $id);
        if ($api_type === 'server_pulse')   return $this->verifyServerPulse($userid, $id);

        return ['status' => 'error', 'message' => 'Type de vote inconnu.'];
    }

    private function verifyTop100Arena(int $userid, int $voteid): array
    {
        if ($this->hasPendingVote($userid, $voteid)) {
            return [
                'status'  => 'not_yet',
                'message' => 'Ton vote n\'est pas encore confirmé. Assure-toi d\'avoir bien voté puis réessaie dans quelques secondes.',
            ];
        }
        return ['status' => 'credited'];
    }

    private function verifyServeurPrive(int $userid, int $voteid): array
    {
        if (!$this->hasPendingVote($userid, $voteid)) {
            return ['status' => 'error', 'message' => 'Aucun vote en attente trouvé.'];
        }

        $api_token = $this->getVoteApiToken($voteid);
        $player_ip = $this->input->ip_address();

        $ch = curl_init('https://serveur-prive.net/api/vote/' . $api_token . '/' . $player_ip);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        $response = curl_exec($ch);
        curl_close($ch);

        if (trim($response) !== '1') {
            log_message('info', 'Serveur-Privé verify: vote non encore détecté pour userid='.$userid.' ip='.$player_ip);
            return [
                'status'  => 'not_yet',
                'message' => 'Ton vote n\'est pas encore détecté. Assure-toi d\'avoir bien voté puis réessaie dans quelques secondes.',
            ];
        }

        return $this->_finalizeVerify($userid, $voteid, 'Serveur-Privé');
    }

    private function verifyServerPulse(int $userid, int $voteid): array
    {
        if (!$this->hasPendingVote($userid, $voteid)) {
            return ['status' => 'error', 'message' => 'Aucun vote en attente trouvé.'];
        }

        $api_token = $this->getVoteApiToken($voteid);
        $player_ip = $this->input->ip_address();

        $ch = curl_init('https://serveur-prive-impulsion.net/api/servers/' . $api_token . '/vote-status/' . $player_ip);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        $response = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($response, true);
        if (!is_array($data) || !isset($data['can_vote']) || $data['can_vote'] === true) {
            log_message('info', 'Server Pulse verify: vote non encore détecté pour userid='.$userid.' ip='.$player_ip);
            return [
                'status'  => 'not_yet',
                'message' => 'Ton vote n\'est pas encore détecté. Assure-toi d\'avoir bien voté puis réessaie dans quelques secondes.',
            ];
        }

        return $this->_finalizeVerify($userid, $voteid, 'Server Pulse');
    }

    public function buildRewardLabels(array $rewards, int $realmid = 1): array
    {
        if (empty($rewards)) return $rewards;

        $itemIds = [];
        foreach ($rewards as $reward) {
            if ((int) $reward->type !== 1 || empty($reward->command)) continue;

            $tokens = array_values(array_filter(
                preg_split('/\s+/', trim($reward->command)),
                'strlen'
            ));

            foreach ($tokens as $token) {
                $id = (int) explode(':', $token)[0];
                if ($id > 0) $itemIds[] = $id;
            }
        }

        $nameMap = [];
        if (!empty($itemIds)) {
            try {
                $uniqueIds = array_unique($itemIds);
                $in        = implode(',', array_map('intval', $uniqueIds));
                $rows = $this->db->query("SELECT entry, name FROM R1_World.item_template WHERE entry IN ({$in})")->result();
                foreach ($rows as $row) {
                    $nameMap[(int) $row->entry] = $row->name;
                }
            } catch (Exception $e) {
                log_message('error', 'buildRewardLabels: impossible d\'accéder à item_template — ' . $e->getMessage());
            }
        }

        foreach ($rewards as &$reward) {
            if (!empty($reward->label)) continue;

            if ((int) $reward->type === 1 && !empty($reward->command)) {

                $tokens = array_values(array_filter(
                    preg_split('/\s+/', trim($reward->command)),
                    'strlen'
                ));

                $pieces = [];
                foreach ($tokens as $token) {
                    $parts = explode(':', $token);
                    $id    = (int) $parts[0];
                    $qty   = isset($parts[1]) ? (int) $parts[1] : 1;
                    $name  = $nameMap[$id] ?? ('Item #' . $id);
                    $pieces[] = $qty > 1 ? $name . ' ×' . $qty : $name;
                }
                $reward->label = implode(', ', $pieces);

            } elseif ((int) $reward->type === 2) {
                $copper = (int) $reward->command;
                $gold   = intdiv($copper, 10000);
                $silver = intdiv($copper % 10000, 100);
                $c      = $copper % 100;
                $parts  = [];
                if ($gold)   $parts[] = $gold   . 'g';
                if ($silver) $parts[] = $silver . 's';
                if ($c)      $parts[] = $c       . 'c';
                $reward->label = implode(' ', $parts) ?: '0c';
            }
        }
        unset($reward);

        return $rewards;
    }

    public function getLeaderboard(string $period = 'weekly', int $limit = 20): array
    {
        [$start, $end] = $this->_getPeriodBounds($period);

        $sql = "
            SELECT vl.idaccount AS userid,
                   u.username,
                   SUM(vl.points) AS points
            FROM votes_logs vl
            INNER JOIN users u ON u.id = vl.idaccount
            WHERE vl.lasttime >= ? AND vl.lasttime <= ?
              AND vl.ip IS NOT NULL AND vl.ip != ''
            GROUP BY vl.idaccount, u.username
            ORDER BY points DESC, MAX(vl.lasttime) ASC
            LIMIT ?
        ";

        return $this->db->query($sql, [$start, $end, $limit])->result();
    }

    public function getNextReset(string $period = 'weekly'): int
    {
        [, $end] = $this->_getPeriodBounds($period);
        return $end + 1;
    }

    private function _getPeriodBounds(string $period): array
    {
        $tz = new DateTimeZone('Europe/Paris');
        $now = new DateTime('now', $tz);

        switch ($period) {
            case 'daily':
                $start = (clone $now)->setTime(0, 0, 0);
                $end   = (clone $start)->modify('+1 day')->modify('-1 second');
                break;
                
            case 'monthly':
                $start = (clone $now)->modify('first day of this month')->setTime(0, 0, 0);
                $end   = (clone $start)->modify('+1 month')->modify('-1 second');
                break;
                
            case 'weekly':
            default:
                $start = (clone $now)->modify('Monday this week')->setTime(0, 0, 0);
                $end   = (clone $start)->modify('+7 days')->modify('-1 second');
                break;
        }

        return [$start->getTimestamp(), $end->getTimestamp()];
    }

    public function processRewards(string $period, int $realmid = 1): array
    {
        $leaderboard = $this->getLeaderboard($period, 20);
        if (empty($leaderboard)) {
            return ['status' => 'no_voters', 'period' => $period];
        }

        $rewards = $this->db
            ->where('period', $period)
            ->order_by('rank_from', 'ASC')
            ->get('vote_rewards')
            ->result();

        if (empty($rewards)) {
            return ['status' => 'no_rewards_configured', 'period' => $period];
        }

        $rewards = $this->buildRewardLabels($rewards);

        $info = $this->wowrealm->getRealm($realmid)->row_array();
        if (!$info) {
            return ['status' => 'realm_not_found', 'realmid' => $realmid];
        }

        $u  = $info['console_username'];
        $p  = $info['console_password'];
        $h  = $info['console_hostname'];
        $po = $info['console_port'];
        $em = $info['emulator'];

        $multirealm = $this->wowrealm->getRealmConnectionData($realmid);
        $report     = [];

        foreach ($leaderboard as $rank => $voter) {
            $rankNum = $rank + 1;
            $reward  = $this->_findReward($rewards, $rankNum);
            if (!$reward) continue;

            $charname = null;

            $pref = $this->db
                ->where('userid', $voter->userid)
                ->limit(1)
                ->get('vote_reward_char')
                ->row();

            if ($pref) {
                $prefRealm = $this->wowrealm->getRealmConnectionData((int) $pref->realm_id);
                if ($prefRealm) {
                    $prefChar = $prefRealm
                        ->select('name')
                        ->where('account', $voter->userid)
                        ->where('name', $pref->charname)
                        ->limit(1)
                        ->get('characters')
                        ->row();
                    if ($prefChar) $charname = $prefChar->name;
                }
            }

            if (!$charname) {
                $charRow = $multirealm
                    ->select('name')
                    ->where('account', $voter->userid)
                    ->order_by('level', 'DESC')
                    ->limit(1)
                    ->get('characters')
                    ->row();
                if ($charRow) $charname = $charRow->name;
            }

            if (!$charname) {
                $report[] = ['rank' => $rankNum, 'user' => $voter->username, 'status' => 'no_char'];
                continue;
            }

            $subject  = 'Récompense classement vote — ' . ucfirst($period);
            $message  = 'Félicitations ! Tu as terminé #' . $rankNum . ' du classement ' . $period . '.';

            switch ((int) $reward->type) {
                case 1:
                    $this->wowrealm->commandSoap('.send items '  . $charname . ' "' . $subject . '" "' . $message . '" ' . $reward->command, $u, $p, $h, $po, $em);
                    break;
                case 2:
                    $this->wowrealm->commandSoap('.send money '  . $charname . ' "' . $subject . '" "' . $message . '" ' . $reward->command, $u, $p, $h, $po, $em);
                    break;
            }

            $report[] = [
                'rank'   => $rankNum,
                'user'   => $voter->username,
                'char'   => $charname,
                'reward' => $reward->label,
                'status' => 'sent',
            ];
        }

        return ['status' => 'done', 'period' => $period, 'report' => $report];
    }

    private function _findReward(array $rewards, int $rank): ?object
    {
        foreach ($rewards as $reward) {
            if ($rank >= (int) $reward->rank_from && $rank <= (int) $reward->rank_to) {
                return $reward;
            }
        }
        return null;
    }

    private function _finalizeVerify(int $userid, int $voteid, string $source): array
    {
        $already = $this->db
            ->where('idaccount', $userid)
            ->where('idvote', $voteid)
            ->where('expired_at >', $this->wowgeneral->getTimestamp())
            ->get('votes_logs')->row();

        if ($already) {
            $this->removePendingVote($userid, $voteid);
            log_message('info', $source . ' verify: déjà crédité pour userid=' . $userid);
            return ['status' => 'credited'];
        }

        $result = $this->creditVotePoints($userid, $voteid);
        if (!$result) {
            log_message('error', $source . ' verify: échec creditVotePoints pour userid=' . $userid);
            return ['status' => 'error', 'message' => 'Erreur lors du crédit des VP.'];
        }

        $this->removePendingVote($userid, $voteid);
        log_message('info', $source . ' verify: VP crédités pour userid=' . $userid);
        return ['status' => 'credited'];
    }

    public function getCountDownHTML($id, $seconds)
    {
        $time = $this->wowgeneral->getTimestamp() + $seconds;
        return '<div class="uk-grid-small uk-child-width-auto uk-margin-small-bottom uk-flex-center" uk-grid uk-countdown="date: '.date('c', $time).'">
                    <div>
                        <div class="uk-countdown-number uk-countdown-hours"></div>
                    </div>
                    <div class="uk-countdown-separator">:</div>
                    <div>
                        <div class="uk-countdown-number uk-countdown-minutes"></div>
                    </div>
                    <div class="uk-countdown-separator">:</div>
                    <div>
                        <div class="uk-countdown-number uk-countdown-seconds"></div>
                    </div>
                </div>';
    }
}