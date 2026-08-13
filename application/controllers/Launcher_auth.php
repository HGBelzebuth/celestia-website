<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Launcher_auth extends CI_Controller {

    public function index($token = '')
    {
        $this->_login($token, '');
    }

    public function store($token = '')
    {
        $this->_login($token, 'en/store');
    }

    public function donate($token = '')
    {
        $this->_login($token, 'en/donate');
    }

    public function parrainage($token = '')
    {
        $this->_login($token, 'en/parrainage');
    }

    public function panel($token = '')
    {
        $this->_login($token, 'en/panel');
    }

    private function _login($token, $destination)
    {
        $target = $destination ? base_url($destination) : base_url();

        if (!$token) {
            redirect($target, 'refresh');
            return;
        }

        $row = $this->db
            ->where('token', $token)
            ->where('expires_at >', time())
            ->limit(1)
            ->get('launcher_sessions')
            ->row();

        if (!$row) {
            redirect($target, 'refresh');
            return;
        }

        $this->wowauth->arraySession((int) $row->userid);

        redirect($target, 'refresh');
    }
}
