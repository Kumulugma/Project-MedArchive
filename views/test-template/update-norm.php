use yii\helpers\Html;
use app\assets\TestTemplateAsset;

TestTemplateAsset::register($this);

$this->title = 'Edytuj normę: ' . $norm->name;
$this->params['breadcrumbs'][] = ['label' => 'Szablony badań', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $template->name, 'url' => ['view', 'id' => $template->id]];
$this->params['breadcrumbs'][] = 'Edytuj normę';
?>

<div class="update-norm">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2"><?= Html::encode($this->title) ?></h1>
    </div>

    <?= $this->render('_norm_form', [
        'template' => $template,
        'parameter' => $parameter,
        'norm' => $norm,
    ]) ?>
</div>
