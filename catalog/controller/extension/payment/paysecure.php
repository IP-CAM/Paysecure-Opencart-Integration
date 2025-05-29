<?php
class ControllerExtensionPaymentPaysecure extends Controller {
    public function index() {
        return $this->load->view('extension/payment/paysecure');
    }

    public function confirm() {
        $this->load->model('checkout/order');
        $order_id = $this->session->data['order_id'];
        $order_info = $this->model_checkout_order->getOrder($order_id);

        $callback_url = $this->url->link('extension/payment/paysecure/callback', '', true);

        $gateway_url = "https://gateway.paysecure.in/redirect"; // change as per actual

        $redirect_url = $gateway_url . "?order_id=" . $order_id . "&amount=" . $order_info['total'] . "&callback=" . urlencode($callback_url);

        $this->response->redirect($redirect_url);
    }

    public function callback() {
        $order_id = $this->request->get['order_id'] ?? 0;
        $status = $this->request->get['status'] ?? 'fail';

        $this->load->model('checkout/order');

        if ($status == 'success') {
            $this->model_checkout_order->addOrderHistory($order_id, $this->config->get('config_order_status_id'));
            $this->response->redirect($this->url->link('checkout/success'));
        } else {
            $this->model_checkout_order->addOrderHistory($order_id, 10); // 10 = failed status id
            $this->response->redirect($this->url->link('checkout/failure'));
        }
    }
}
