<?php
namespace app\commands;

use Yii;
use yii\console\Controller;
use yii\console\ExitCode;
use app\models\TestQueue;

class ReminderController extends Controller
{
    public function actionSendReminders()
    {
        $upcomingTests = TestQueue::find()
            ->where(['status' => TestQueue::STATUS_PENDING])
            ->andWhere(['reminder_sent' => false])
            ->andWhere(['<=', 'scheduled_date', date('Y-m-d', strtotime('+7 days'))])
            ->andWhere(['>=', 'scheduled_date', date('Y-m-d')])
            ->with('testTemplate')
            ->all();
        
        $sent = 0;
        
        foreach ($upcomingTests as $test) {
            if ($this->sendReminderEmail($test)) {
                $test->reminder_sent = true;
                $test->save(false);
                $sent++;
            }
        }
        
        echo "Wysłano {$sent} przypomnień.\n";
        return ExitCode::OK;
    }
    
    private function sendReminderEmail($test)
    {
        try {
            // Przykładowe wysyłanie maila
            $subject = 'Przypomnienie o badaniu: ' . $test->testTemplate->name;
            $body = "Dzień dobry,\n\n";
            $body .= "Przypominamy o zaplanowanym badaniu:\n";
            $body .= "Badanie: {$test->testTemplate->name}\n";
            $body .= "Data: " . Yii::$app->formatter->asDate($test->scheduled_date) . "\n";
            
            if ($test->comment) {
                $body .= "Komentarz: {$test->comment}\n";
            }
            
            $body .= "\nPozdrawiamy,\nSystem MedArchive";
            
            // Tutaj kod do rzeczywistego wysyłania maila
            // Yii::$app->mailer->compose()...
            
            return true;
        } catch (\Exception $e) {
            Yii::error('Błąd wysyłania przypomnienia: ' . $e->getMessage());
            return false;
        }
    }
}