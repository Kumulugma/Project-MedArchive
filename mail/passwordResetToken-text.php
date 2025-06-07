<?php
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $user app\models\User */

$resetLink = Url::to(['site/reset-password', 'token' => $user->password_reset_token], true);
?>

MEDARCHIVE - RESET HASŁA
========================

Witaj <?= $user->username ?>!

Otrzymałeś ten email, ponieważ ktoś (prawdopodobnie Ty) zażądał resetowania hasła dla Twojego konta w systemie MedArchive.

Aby ustawić nowe hasło, wejdź na poniższy link:

<?= $resetLink ?>

UWAGA: Ten link jest ważny przez 1 godzinę od momentu wysłania tego emaila. Po tym czasie będziesz musiał ponownie zażądać resetowania hasła.

Jeśli nie żądałeś resetowania hasła, zignoruj ten email. Twoje hasło pozostanie bez zmian.

--
MedArchive - System archiwizacji wyników badań medycznych
© <?= date('Y') ?> Wszystkie prawa zastrzeżone