<?php
class ControllerExtensionPaymentPaysecure extends Controller {
    private $error = [];

    public function index() {
        $this->load->language('extension/payment/paysecure');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('setting/setting');

        if ($this->request->server['REQUEST_METHOD'] == 'POST' && $this->validate()) {
            $this->model_setting_setting->editSetting('payment_paysecure', $this->request->post);
            $this->session->data['success'] = $this->language->get('text_success');
            $this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . 
            $this->session->data['user_token'] . '&type=payment', true));
        }

        $data['action'] = $this->url->link('extension/payment/paysecure', 'user_token=' . $this->session->data['user_token'], true);
        $data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true);

        $data['payment_paysecure_api_key'] = $this->request->post['payment_paysecure_api_key'] ?? $this->config->get('payment_paysecure_api_key');
        $data['payment_paysecure_status'] = $this->request->post['payment_paysecure_status'] ?? $this->config->get('payment_paysecure_status');

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/payment/paysecure', $data));
    }

    protected function validate() {
        return true; // Add permission checks if needed
    }
}
