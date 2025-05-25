<?php
class ModelPaymentPaysecure extends Model {
    private $_group = 'paysecure';

    public function install() {
        $this->load->model('setting/setting');
        $this->model_setting_setting->editSetting($this->_group, array(
            'paysecure_status'        => 0,
            'paysecure_public_key'    => '',
            'paysecure_secret_key'    => '',
            'paysecure_test_mode'     => 0,
            'paysecure_payment_title' => 'Paysecure Payment',
        ));

        $this->db->query("
            CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "paysecure_transaction` (
              `id` INT(11) NOT NULL AUTO_INCREMENT,
              `order_id` INT(11) NOT NULL,
              `transaction_id` CHAR(45) NOT NULL,
              `amount` DECIMAL(10,2) NOT NULL,
              `date_added` DATETIME NOT NULL,
              PRIMARY KEY (`id`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8;
        ");
    }

    public function uninstall() {
        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "paysecure_transaction`;");
    }

    public function createPurchase($order_id, $amount, $currency, $card_token) {
        $keys = $this->_getKeys();
        if (!$keys) return ['error' => 'Paysecure is disabled'];

        try {
            $data = [
                'amount'   => $amount,
                'currency' => $currency,
                'token'    => $card_token,
                'order_id' => $order_id,
            ];

            $response = $this->_callApi('/v1/purchase', 'POST', $keys, $data);
            $parsed = json_decode($response, true);

            if (isset($parsed['transaction_id'])) {
                $this->_saveTransaction($order_id, $parsed['transaction_id'], $amount);
            }

            return $parsed;
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    public function payout($amount, $destination_account_id) {
        $keys = $this->_getKeys();
        if (!$keys) return ['error' => 'Paysecure is disabled'];

        try {
            $data = [
                'amount'   => $amount,
                'account'  => $destination_account_id,
            ];

            $response = $this->_callApi('/v1/payout', 'POST', $keys, $data);
            return json_decode($response, true);
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    private function _saveTransaction($order_id, $transaction_id, $amount) {
        $this->db->query("
            INSERT INTO `" . DB_PREFIX . "paysecure_transaction`
            SET order_id = '" . (int)$order_id . "',
                transaction_id = '" . $this->db->escape($transaction_id) . "',
                amount = '" . (float)$amount . "',
                date_added = NOW()
        ");
    }

    private function _getKeys() {
        if ($this->config->get('paysecure_status') && $this->config->get('paysecure_test_mode')) {
            return [
                'pkey' => $this->config->get('paysecure_public_key_test'),
                'skey' => $this->config->get('paysecure_secret_key_test')
            ];
        } elseif ($this->config->get('paysecure_status')) {
            return [
                'pkey' => $this->config->get('paysecure_public_key'),
                'skey' => $this->config->get('paysecure_secret_key')
            ];
        } else {
            return false;
        }
    }

    private function _callApi($endpoint, $method, $keys, $data = []) {
        $url = 'https://api.paysecure.net/api/v1' . $endpoint;
        $curl = curl_init();

        $headers = [
            'Authorization: Basic ' . base64_encode($keys['skey'] . ':'),
            'Content-Type: application/json'
        ];

        $options = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
        ];

        if ($method === 'POST') {
            $options[CURLOPT_POST] = true;
            $options[CURLOPT_POSTFIELDS] = json_encode($data);
        }

        curl_setopt_array($curl, $options);
        $response = curl_exec($curl);

        if (curl_errno($curl)) {
            throw new Exception(curl_error($curl));
        }

        curl_close($curl);
        return $response;
    }
}
