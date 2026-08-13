<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * BlizzCMS — Donate Controller
 * Optimisations :
 * - Validation de $_GET avant usage (évite notices PHP)
 * - Typage strict des paramètres
 * - Redirection après POST uniquement si PayPal a bien créé le paiement
 */

require './vendor/autoload.php';

use \PayPal\Api\Payment;
use \PayPal\Api\PaymentExecution;

class Donate extends MX_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('donate_model');
        $this->load->config('donate');

        if (!ini_get('date.timezone')) {
            date_default_timezone_set($this->config->item('timezone'));
        }

        if (!$this->wowgeneral->getMaintenance()) {
            redirect(base_url('maintenance'), 'refresh');
        }

        if (!$this->wowmodule->getDonationStatus()) {
            redirect(base_url(), 'refresh');
        }

        if (!$this->wowauth->isLogged()) {
            redirect(base_url('login'), 'refresh');
        }
    }

    public function index()
    {
        $activeOffer = null;

        if ($this->db->table_exists('launcher_donate_offer')) {
            $offerRow = $this->db->where('id', 1)->limit(1)->get('launcher_donate_offer')->row();
            if ($offerRow && (int) $offerRow->donate_id > 0) {
                $pack = $this->db->select('id, name, price, points')
                                 ->where('id', (int) $offerRow->donate_id)
                                 ->limit(1)
                                 ->get('donate')
                                 ->row();
                if ($pack) {
                    $activeOffer = [
                        'donate_id'   => (int) $offerRow->donate_id,
                        'name'        => ($offerRow->promo_name  ?? '') !== '' ? $offerRow->promo_name  : $pack->name,
                        'price'       => ($offerRow->promo_price ?? '') !== '' ? (float) $offerRow->promo_price : (float) $pack->price,
                        'points'      => (int) ($offerRow->promo_points ?? 0) > 0 ? (int) $offerRow->promo_points : (int) $pack->points,
                        'message'     => $offerRow->message ?? '',
                        'base_price'  => (float) $pack->price,
                        'base_points' => (int) $pack->points,
                    ];
                }
            }
        }

        $data = [
            'pagetitle'   => $this->lang->line('tab_donate'),
            'activeOffer' => $activeOffer,
        ];

        $this->template->build('index', $data);
    }

    public function check($id)
    {
        /* Validation des paramètres GET avant tout usage */
        $paymentId = isset($_GET['paymentId']) ? trim($_GET['paymentId']) : '';
        $payerId   = isset($_GET['PayerID'])   ? trim($_GET['PayerID'])   : '';

        if ($paymentId === '' || $payerId === '') {
            redirect(base_url($this->lang->lang() . '/donate'), 'refresh');
            return;
        }

        $execute = new PaymentExecution();
        $payment = Payment::get($paymentId, $this->donate_model->getApi());
        $execute->setPayerId($payerId);

        try {
            $payment->execute($execute, $this->donate_model->getApi());
        } catch (Exception $e) {
            /* Log l'erreur plutôt que die() brut */
            log_message('error', 'PayPal execute error: ' . $e->getMessage());
            $this->session->set_flashdata('donation_status', 'error');
            redirect(base_url($this->lang->lang() . '/donate'), 'refresh');
            return;
        }

        $this->donate_model->completeTransaction((int) $id, $paymentId);
    }

    public function canceled()
    {
        $this->session->set_flashdata('donation_status', 'canceled');
        redirect(base_url($this->lang->lang() . '/donate'), 'refresh');
    }
}
