<?php
namespace app\models\search;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\TestResult;

class TestResultSearch extends TestResult
{
    public $testTemplateName;

    public function rules()
    {
        return [
            [['id', 'test_template_id'], 'integer'],
            [['test_date', 'comment', 'testTemplateName'], 'safe'],
            [['has_abnormal_values'], 'boolean'],
        ];
    }

    public function scenarios()
    {
        return Model::scenarios();
    }

    public function search($params)
    {
        $query = TestResult::find()->joinWith('testTemplate');

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => [
                'defaultOrder' => [
                    'test_date' => SORT_DESC,
                    'created_at' => SORT_DESC,
                ]
            ],
        ]);

        $dataProvider->sort->attributes['testTemplateName'] = [
            'asc' => ['test_templates.name' => SORT_ASC],
            'desc' => ['test_templates.name' => SORT_DESC],
        ];

        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }

        $query->andFilterWhere([
            'test_results.id' => $this->id,
            'test_results.test_template_id' => $this->test_template_id,
            'test_results.test_date' => $this->test_date,
            'test_results.has_abnormal_values' => $this->has_abnormal_values,
        ]);

        $query->andFilterWhere(['like', 'test_results.comment', $this->comment])
              ->andFilterWhere(['like', 'test_templates.name', $this->testTemplateName]);

        return $dataProvider;
    }
}