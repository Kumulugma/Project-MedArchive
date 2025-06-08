<?php

use yii\helpers\Html;
use app\assets\TestTemplateAsset;

/* @var $this yii\web\View */
/* @var $template app\models\TestTemplate */
/* @var $parameter app\models\TestParameter */
/* @var $norm app\models\ParameterNorm */

TestTemplateAsset::register($this);

$this->title = 'Edytuj normę: ' . $norm->name;
$this->params['breadcrumbs'][] = ['label' => 'Szablony badań', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $template->name, 'url' => ['view', 'id' => $template->id]];
$this->params['breadcrumbs'][] = 'Edytuj normę';

// Focus on warnings section if requested
$focusWarnings = Yii::$app->request->get('focus') === 'warnings';
?>

<div class="update-norm">
    <div class="page-header">
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
            <h1 class="h2">
                <i class="fas fa-edit"></i>
                <?= Html::encode($this->title) ?>
            </h1>
            <div class="btn-toolbar mb-2 mb-md-0">
                <?= Html::a('<i class="fas fa-arrow-left"></i> Powrót do szablonu', ['view', 'id' => $template->id], ['class' => 'btn btn-outline-secondary']) ?>
            </div>
        </div>
    </div>

    <div class="alert-container"></div>

    <?php if ($focusWarnings): ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i>
            <strong>Konfiguracja ostrzeżeń:</strong> Przewiń w dół do sekcji "Konfiguracja ostrzeżeń" aby ustawić marginesy ostrzeżeń.
        </div>
    <?php endif; ?>

    <?= $this->render('_enhanced_norm_form', [
        'template' => $template,
        'parameter' => $parameter,
        'norm' => $norm,
    ]) ?>
</div>

<?php if ($focusWarnings): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Scroll to warnings section and expand it
    const warningsCheckbox = document.querySelector('input[name="ParameterNorm[warning_enabled]"]');
    if (warningsCheckbox) {
        // Enable warnings
        warningsCheckbox.checked = true;
        toggleWarningFields(true);
        
        // Scroll to warnings section
        setTimeout(function() {
            warningsCheckbox.scrollIntoView({ behavior: 'smooth', block: 'center' });
            
            // Highlight the section
            const warningsSection = warningsCheckbox.closest('.mt-4');
            if (warningsSection) {
                warningsSection.style.border = '2px solid #007bff';
                warningsSection.style.borderRadius = '5px';
                warningsSection.style.padding = '1rem';
                
                setTimeout(function() {
                    warningsSection.style.border = '';
                    warningsSection.style.borderRadius = '';
                    warningsSection.style.padding = '';
                }, 3000);
            }
        }, 500);
    }
});
</script>
<?php endif; ?>