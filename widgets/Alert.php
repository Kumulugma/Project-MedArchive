<?php
namespace app\widgets;

use yii\bootstrap5\Alert as BaseAlert;

class Alert extends \yii\bootstrap5\Widget
{
    public function run()
    {
        $session = \Yii::$app->session;
        $flashes = $session->getAllFlashes();
        $output = '';

        foreach ($flashes as $type => $flash) {
            if (is_array($flash)) {
                foreach ($flash as $message) {
                    $output .= $this->renderAlert($type, $message);
                }
            } else {
                $output .= $this->renderAlert($type, $flash);
            }
        }

        return $output;
    }

    private function renderAlert($type, $message)
    {
        $alertClass = $this->getAlertClass($type);
        return BaseAlert::widget([
            'body' => $message,
            'options' => ['class' => $alertClass]
        ]);
    }

    private function getAlertClass($type)
    {
        switch ($type) {
            case 'error':
                return 'alert-danger';
            case 'success':
                return 'alert-success';
            case 'info':
                return 'alert-info';
            case 'warning':
                return 'alert-warning';
            default:
                return 'alert-info';
        }
    }
}
