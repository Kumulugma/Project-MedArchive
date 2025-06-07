<?php
use yii\helpers\Html;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $user app\models\User */

$resetLink = Url::to(['site/reset-password', 'token' => $user->password_reset_token], true);
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset hasła - MedArchive</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #2c5aa0;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .header h1 {
            color: #2c5aa0;
            margin: 0;
        }
        .content {
            margin-bottom: 30px;
        }
        .button {
            display: inline-block;
            padding: 12px 30px;
            background-color: #2c5aa0;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            text-align: center;
            margin: 20px 0;
        }
        .button:hover {
            background-color: #1e3d70;
        }
        .footer {
            border-top: 1px solid #eee;
            padding-top: 20px;
            text-align: center;
            color: #666;
            font-size: 14px;
        }
        .warning {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>MedArchive</h1>
            <p>Reset hasła do Twojego konta</p>
        </div>
        
        <div class="content">
            <p>Witaj <?= Html::encode($user->username) ?>!</p>
            
            <p>Otrzymałeś ten email, ponieważ ktoś (prawdopodobnie Ty) zażądał resetowania hasła dla Twojego konta w systemie MedArchive.</p>
            
            <p>Aby ustawić nowe hasło, kliknij w poniższy przycisk:</p>
            
            <div style="text-align: center;">
                <a href="<?= Html::encode($resetLink) ?>" class="button">
                    Zresetuj hasło
                </a>
            </div>
            
            <p>Jeśli przycisk nie działa, skopiuj i wklej poniższy link do przeglądarki:</p>
            <p style="word-break: break-all; background: #f8f9fa; padding: 10px; border-radius: 5px;">
                <?= Html::encode($resetLink) ?>
            </p>
            
            <div class="warning">
                <strong>Uwaga:</strong> Ten link jest ważny przez 1 godzinę od momentu wysłania tego emaila. 
                Po tym czasie będziesz musiał ponownie zażądać resetowania hasła.
            </div>
            
            <p>Jeśli nie żądałeś resetowania hasła, zignoruj ten email. Twoje hasło pozostanie bez zmian.</p>
        </div>
        
        <div class="footer">
            <p><strong>MedArchive</strong> - System archiwizacji wyników badań medycznych</p>
            <p>&copy; <?= date('Y') ?> Wszystkie prawa zastrzeżone</p>
        </div>
    </div>
</body>
</html>