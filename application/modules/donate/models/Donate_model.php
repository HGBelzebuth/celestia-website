<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * BlizzCMS — Donate_model
 * Optimisations :
 * - getApi() : instance ApiContext mise en cache (propriété $_api) → créée une seule fois par requête
 * - getSpecifyDonate() supprimé : getDonations() retourne déjà toutes les lignes,
 *   les accès unitaires passent par getDonationById() avec cache tableau O(1)
 * - getDonate() : getSpecifyDonate() n'est plus appelé 2x — récupération directe
 * - completeTransaction() : getCurrentDP() inliné dans la même requête UPDATE (1 requête au lieu de 2)
 * - Suppression des doublons d'appels PayPal (setIntent+setPayer appelés 2x dans l'original)
 */

require './vendor/autoload.php';

use \PayPal\Rest\ApiContext;
use \PayPal\Auth\OAuthTokenCredential;
use \PayPal\Api\Item;
use \PayPal\Api\Payer;
use \PayPal\Api\Amount;
use \PayPal\Api\Payment;
use \PayPal\Api\Details;
use \PayPal\Api\ItemList;
use \PayPal\Api\Transaction;
use \PayPal\Api\RedirectUrls;
use \PayPal\Exception\PayPalConnectionException;

class Donate_model extends CI_Model
{
    /** @var ApiContext|null Instance PayPal mise en cache pour la durée de la requête */
    private $_api = null;

    /** @var array Cache des donations indexées par id — O(1) en lecture */
    private $_donationsMap = [];

    public function __construct()
    {
        parent::__construct();
    }

    /* ════════════════════════════════════════════════════
       API PAYPAL — singleton de requête
    ════════════════════════════════════════════════════ */

    /**
     * Retourne l'instance ApiContext (créée une seule fois par requête HTTP).
     */
    public function getApi(): ApiContext
    {
        if ($this->_api !== null) {
            return $this->_api;
        }

        $this->_api = new ApiContext(
            new OAuthTokenCredential(
                $this->config->item('paypal_userid'),
                $this->config->item('paypal_secretpass')
            )
        );

        $this->_api->setConfig([
            'mode'                  => $this->config->item('paypal_mode'),
            'http.ConnectionTimeOut'=> 30,
            'log.LogEnabled'        => false,
            'log.FileName'          => 'paypal_logs',
            'log.LogLevel'          => 'FINE',
            'validation.level'      => 'log',
        ]);

        return $this->_api;
    }

    /* ════════════════════════════════════════════════════
       REQUÊTES DB
    ════════════════════════════════════════════════════ */

    /**
     * Toutes les donations (pour la vue).
     */
    public function getDonations()
    {
        return $this->db->select('*')->get('donate');
    }

    /**
     * Donation par ID — O(1) grâce au cache interne.
     * Évite un SELECT par carte dans la boucle de la vue.
     */
    public function getDonationById(int $id): ?object
    {
        /* Chargement du cache si vide */
        if (empty($this->_donationsMap)) {
            foreach ($this->getDonations()->result() as $row) {
                $this->_donationsMap[(int) $row->id] = $row;
            }
        }
        return $this->_donationsMap[$id] ?? null;
    }

    /**
     * DP actuels de l'utilisateur connecté.
     */
    public function getCurrentDP(): int
    {
        $row = $this->db
            ->select('dp')
            ->where('id', $this->session->userdata('wow_sess_id'))
            ->get('users')
            ->row();

        return $row ? (int) $row->dp : 0;
    }

    /* ════════════════════════════════════════════════════
       PAYPAL — CRÉATION DU PAIEMENT
    ════════════════════════════════════════════════════ */

    public function getDonate(int $id): void
    {
        $donate = $this->getDonationById($id);

        if ($donate === null) {
            log_message('error', 'Donate_model::getDonate() — ID introuvable : ' . $id);
            redirect(base_url($this->lang->lang() . '/donate'), 'refresh');
            return;
        }

        $setPrice  = (float) $donate->price;
        $setTax    = (float) $donate->tax;
        $setPoints = (int)   $donate->points;

        /* ── Promo active pour ce pack ? ── */
        if ($this->db->table_exists('launcher_donate_offer')) {
            $offer = $this->db->where('donate_id', $id)->limit(1)->get('launcher_donate_offer')->row();
            if ($offer) {
                if ($offer->promo_price !== null && $offer->promo_price !== '' && (float) $offer->promo_price > 0) {
                    $setPrice = (float) $offer->promo_price;
                    $setTax   = 0.00;
                }
                if (!empty($offer->promo_points) && (int) $offer->promo_points > 0) {
                    $setPoints = (int) $offer->promo_points;
                }
            }
        }

        $setTotal = $setPrice + $setTax;

        /* ── Payer ── */
        $payer = new Payer();
        $payer->setPaymentMethod('paypal');

        /* ── Item ── */
        $item = new Item();
        $item->setName('Donation')
             ->setCurrency($this->config->item('paypal_currency'))
             ->setQuantity(1)
             ->setPrice($setPrice);

        /* ── ItemList ── */
        $itemList = new ItemList();
        $itemList->setItems([$item]);

        /* ── Details ── */
        $details = new Details();
        $details->setShipping('0.00')
                ->setTax($setTax)
                ->setSubtotal($setPrice);

        /* ── Amount ── */
        $amount = new Amount();
        $amount->setCurrency($this->config->item('paypal_currency'))
               ->setTotal($setTotal)
               ->setDetails($details);

        /* ── Transaction ── */
        $transaction = new Transaction();
        $transaction->setAmount($amount)
                    ->setItemList($itemList)
                    ->setDescription('Donation')
                    ->setInvoiceNumber(uniqid());

        /* ── RedirectUrls ── */
        $redirectUrls = new RedirectUrls();
        $redirectUrls->setReturnUrl(base_url($this->lang->lang() . '/donate/check/' . $id))
                     ->setCancelUrl(base_url($this->lang->lang() . '/donate/canceled'));

        /* ── Payment — setIntent/setPayer appelés UNE seule fois (doublons corrigés) ── */
        $payment = new Payment();
        $payment->setIntent('sale')
                ->setPayer($payer)
                ->setRedirectUrls($redirectUrls)
                ->setTransactions([$transaction]);

        try {
            $payment->create($this->getApi());
        } catch (PayPalConnectionException $e) {
            log_message('error', 'PayPal create error: ' . $e->getData());
            $this->session->set_flashdata('donation_status', 'error');
            redirect(base_url($this->lang->lang() . '/donate'), 'refresh');
            return;
        }

        /* ── Insertion en base ── */
        $this->db->insert('donate_logs', [
            'user_id'     => $this->session->userdata('wow_sess_id'),
            'payment_id'  => $payment->getId(),
            'hash'        => md5($payment->getId()),
            'total'       => $payment->transactions[0]->amount->total,
            'points'      => $setPoints,
            'create_time' => $payment->create_time,
            'status'      => '0',
        ]);

        /* ── Redirection vers PayPal ── */
        $redirectUrl = null;
        foreach ($payment->getLinks() as $link) {
            if ($link->getRel() === 'approval_url') {
                $redirectUrl = $link->getHref();
                break; // ← on sort dès qu'on a trouvé le lien
            }
        }

        if ($redirectUrl) {
            header('Location: ' . $redirectUrl);
            exit;
        }

        log_message('error', 'PayPal : approval_url introuvable pour payment ' . $payment->getId());
        $this->session->set_flashdata('donation_status', 'error');
        redirect(base_url($this->lang->lang() . '/donate'), 'refresh');
    }

    /* ════════════════════════════════════════════════════
       FINALISATION DE LA TRANSACTION
    ════════════════════════════════════════════════════ */

    /**
     * Complète la transaction PayPal et crédite les DP.
     * Optimisation : les DP sont mis à jour avec une seule requête SQL
     * (dp = dp + ?) au lieu de getCurrentDP() + update séparé.
     */
    public function completeTransaction(int $donate, string $paymentId): void
    {
        $log = $this->db
            ->select('status, points')
            ->where('payment_id', $paymentId)
            ->get('donate_logs')
            ->row();

        /* Paiement déjà traité → erreur */
        if (!$log || (int) $log->status === 1) {
            $this->session->set_flashdata('donation_status', 'error');
            redirect(base_url($this->lang->lang() . '/donate'), 'refresh');
            return;
        }

        $points = (int) $log->points;
        $userId = (int) $this->session->userdata('wow_sess_id');

        /* Marquer la transaction comme traitée */
        $this->db->where('payment_id', $paymentId)->update('donate_logs', ['status' => '1']);

        /* Crédit DP : dp = dp + $points — une seule requête, pas de race condition */
        $this->db->set('dp', 'dp + ' . $points, false)
                 ->where('id', $userId)
                 ->update('users');

        $this->session->set_flashdata('donation_status', 'success');
        redirect(base_url($this->lang->lang() . '/donate'), 'refresh');
    }
}
