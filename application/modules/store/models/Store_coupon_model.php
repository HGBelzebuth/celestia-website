<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Store_coupon_model
 * Gestion des coupons de réduction du store.
 *
 * Table store_coupons :
 *   id, code, type (percent|fixed), value, currency (dp|vp|both),
 *   min_amount, expires_at, active, created_at
 *
 * Table store_coupon_usage :
 *   coupon_id, userid, used_at  — usage unique par compte
 */
class Store_coupon_model extends CI_Model
{
    // ── Validation ────────────────────────────────────────────

    /**
     * Valide un code coupon pour un utilisateur et un panier donné.
     *
     * @param  string $code       Code saisi par l'utilisateur
     * @param  int    $userid     ID du compte
     * @param  int    $totalDp    Total DP du panier
     * @param  int    $totalVp    Total VP du panier
     * @return array  ['valid' => bool, 'error' => string|null, 'coupon' => row|null,
     *                 'discountDp' => int, 'discountVp' => int,
     *                 'finalDp' => int,   'finalVp' => int]
     */
    public function validate($code, $userid, $totalDp, $totalVp)
    {
        $code = strtoupper(trim($code));

        if (empty($code)) {
            return $this->_fail('empty_code');
        }

        // Récupérer le coupon
        $coupon = $this->db
            ->where('code',   $code)
            ->where('active', 1)
            ->get('store_coupons')
            ->row();

        if (!$coupon) {
            return $this->_fail('invalid_code');
        }

        // Vérifier expiration
        if ($coupon->expires_at !== null && strtotime($coupon->expires_at) < time()) {
            return $this->_fail('expired');
        }

        // Vérifier usage unique par compte
        $alreadyUsed = $this->db
            ->where('coupon_id', $coupon->id)
            ->where('userid',    $userid)
            ->count_all_results('store_coupon_usage');

        if ($alreadyUsed > 0) {
            return $this->_fail('already_used');
        }

        // Vérifier montant minimum
        $relevantTotal = $this->_getRelevantTotal($coupon->currency, $totalDp, $totalVp);
        if ((int)$coupon->min_amount > 0 && $relevantTotal < (int)$coupon->min_amount) {
            return $this->_fail('min_amount', ['min' => (int)$coupon->min_amount, 'currency' => $coupon->currency]);
        }

        // Calculer la réduction
        list($discountDp, $discountVp) = $this->_calcDiscount($coupon, $totalDp, $totalVp);

        $finalDp = max(0, $totalDp - $discountDp);
        $finalVp = max(0, $totalVp - $discountVp);

        return [
            'valid'      => true,
            'error'      => null,
            'coupon'     => $coupon,
            'discountDp' => $discountDp,
            'discountVp' => $discountVp,
            'finalDp'    => $finalDp,
            'finalVp'    => $finalVp,
        ];
    }

    /**
     * Enregistre l'utilisation d'un coupon (appelé après checkout réussi).
     */
    public function markUsed($couponId, $userid)
    {
        $this->db->insert('store_coupon_usage', [
            'coupon_id' => (int)$couponId,
            'userid'    => (int)$userid,
            'used_at'   => time(),
        ]);
    }

    // ── Helpers privés ────────────────────────────────────────

    private function _fail($error, $extra = [])
    {
        return array_merge([
            'valid'      => false,
            'error'      => $error,
            'coupon'     => null,
            'discountDp' => 0,
            'discountVp' => 0,
            'finalDp'    => 0,
            'finalVp'    => 0,
        ], $extra);
    }

    private function _getRelevantTotal($currency, $totalDp, $totalVp)
    {
        if ($currency === 'dp')   return $totalDp;
        if ($currency === 'vp')   return $totalVp;
        return $totalDp + $totalVp; // both
    }

    private function _calcDiscount($coupon, $totalDp, $totalVp)
    {
        $discountDp = 0;
        $discountVp = 0;
        $val = (float)$coupon->value;

        $affectsDp = in_array($coupon->currency, ['dp', 'both']);
        $affectsVp = in_array($coupon->currency, ['vp', 'both']);

        if ($coupon->type === 'percent') {
            if ($affectsDp) $discountDp = (int)round($totalDp * $val / 100);
            if ($affectsVp) $discountVp = (int)round($totalVp * $val / 100);
        } else { // fixed
            if ($affectsDp) $discountDp = min($totalDp, (int)$val);
            if ($affectsVp) $discountVp = min($totalVp, (int)$val);
        }

        return [$discountDp, $discountVp];
    }
}
