<?php
namespace app\models\search;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\TestQueue;

class TestQueueSearch extends TestQueue
{
    public $testTemplateName;

    public function rules()
    {
        return [
            [['id', 'test_template_id'], 'integer'],
            [['scheduled_date', 'comment', 'status', 'testTemplateName'], 'safe'],
            [['reminder_sent'], 'boolean'],
        ];
    }

    public function scenarios()
    {
        return Model::scenarios();
    }

    public function search($params)
    {
        $query = TestQueue::find()->joinWith('testTemplate');

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => [
                'defaultOrder' => [
                    'scheduled_date' => SORT_ASC,
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
            'test_queue.id' => $this->id,
            'test_queue.test_template_id' => $this->test_template_id,
            'test_queue.scheduled_date' => $this->scheduled_date,
            'test_queue.status' => $this->status,
            'test_queue.reminder_sent' => $this->reminder_sent,
        ]);

        $query->andFilterWhere(['like', 'test_queue.comment', $this->comment])
              ->andFilterWhere(['like', 'test_templates.name', $this->testTemplateName]);

        return $dataProvider;
    }
}