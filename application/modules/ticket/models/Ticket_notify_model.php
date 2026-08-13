<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Ticket_notify_model — Celestia-WoW
 * ══════════════════════════════════════════════════════════
 * Gère la table staff_notifications.
 * ══════════════════════════════════════════════════════════
 */
class Ticket_notify_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Retourne tous les membres du staff actifs.
     * @return object[]
     */
    public function getActiveStaff(): array
    {
        return $this->db
            ->where('active', 1)
            ->get('staff_notifications')
            ->result();
    }

    /**
     * Ajoute ou met à jour un membre du staff.
     */
    public function upsertStaff(int $account_id, string $email, ?string $discord_id = null, bool $notify_email = true, bool $notify_discord = true): bool
    {
        $exists = $this->db->where('account_id', $account_id)->limit(1)->get('staff_notifications')->row();

        $data = [
            'email'           => $email,
            'discord_id'      => $discord_id,
            'notify_email'    => (int) $notify_email,
            'notify_discord'  => (int) $notify_discord,
            'active'          => 1,
        ];

        if ($exists) {
            $this->db->where('account_id', $account_id)->update('staff_notifications', $data);
        } else {
            $data['account_id'] = $account_id;
            $this->db->insert('staff_notifications', $data);
        }

        return $this->db->affected_rows() > 0;
    }

    /**
     * Active ou désactive un membre du staff.
     */
    public function setActive(int $account_id, bool $active): void
    {
        $this->db->where('account_id', $account_id)->update('staff_notifications', ['active' => (int) $active]);
    }

    /**
     * Supprime un membre du staff.
     */
    public function removeStaff(int $account_id): void
    {
        $this->db->where('account_id', $account_id)->delete('staff_notifications');
    }
}
