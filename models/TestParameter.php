<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

/**
 * TestParameter model
 *
 * @property int $id
 * @property int $test_template_id
 * @property string $name
 * @property string|null $description
 * @property string|null $unit
 * @property string $type
 * @property int|null $order_index
 * @property string $created_at
 * @property string $updated_at
 *
 * @property TestTemplate $testTemplate
 * @property ParameterNorm[] $norms
 * @property ParameterNorm $primaryNorm
 * @property ResultValue[] $resultValues
 */
class TestParameter extends ActiveRecord
{
    const TYPE_POSITIVE_NEGATIVE = 'positive_negative';
    const TYPE_RANGE = 'range';
    const TYPE_SINGLE_THRESHOLD = 'single_threshold';
    const TYPE_MULTIPLE_THRESHOLDS = 'multiple_thresholds';
    const TYPE_NUMERIC = 'numeric';
    const TYPE_TEXT = 'text';

    public static function tableName()
    {
        return '{{%test_parameters}}';
    }

    public function behaviors()
    {
        return [
            TimestampBehavior::class,
        ];
    }

    public function rules()
    {
        return [
            [['test_template_id', 'name', 'type'], 'required'],
            [['test_template_id', 'order_index'], 'integer'],
            [['name'], 'string', 'max' => 255],
            [['description'], 'string', 'max' => 1000],
            [['unit'], 'string', 'max' => 50],
            [['type'], 'string', 'max' => 50],
            [['type'], 'in', 'range' => [
                self::TYPE_POSITIVE_NEGATIVE,
                self::TYPE_RANGE,
                self::TYPE_SINGLE_THRESHOLD,
                self::TYPE_MULTIPLE_THRESHOLDS,
                self::TYPE_NUMERIC,
                self::TYPE_TEXT,
            ]],
            [['order_index'], 'default', 'value' => 0],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'test_template_id' => 'Szablon badania',
            'name' => 'Nazwa parametru',
            'description' => 'Opis parametru',
            'unit' => 'Jednostka',
            'type' => 'Typ parametru',
            'order_index' => 'Kolejność',
            'created_at' => 'Utworzono',
            'updated_at' => 'Zaktualizowano',
        ];
    }

    public function getTestTemplate()
    {
        return $this->hasOne(TestTemplate::class, ['id' => 'test_template_id']);
    }

    public function getNorms()
    {
        return $this->hasMany(ParameterNorm::class, ['parameter_id' => 'id']);
    }

    public function getPrimaryNorm()
    {
        return $this->hasOne(ParameterNorm::class, ['parameter_id' => 'id'])->where(['is_primary' => true]);
    }

    public function getResultValues()
    {
        return $this->hasMany(ResultValue::class, ['parameter_id' => 'id']);
    }

    public static function getTypeOptions()
    {
        return [
            self::TYPE_POSITIVE_NEGATIVE => 'Pozytywny/Negatywny',
            self::TYPE_RANGE => 'Zakres min-max',
            self::TYPE_SINGLE_THRESHOLD => 'Pojedynczy próg',
            self::TYPE_MULTIPLE_THRESHOLDS => 'Wiele progów',
            self::TYPE_NUMERIC => 'Numeryczny',
            self::TYPE_TEXT => 'Tekstowy',
        ];
    }

    /**
     * Sprawdza czy parametr ma skonfigurowane normy
     */
    public function hasNorms()
    {
        return !empty($this->norms);
    }

    /**
     * Sprawdza czy parametr ma włączone ostrzeżenia
     */
    public function hasWarningsEnabled()
    {
        foreach ($this->norms as $norm) {
            if ($norm->warning_enabled) {
                return true;
            }
        }
        return false;
    }

    /**
     * Zwraca liczbę norm dla parametru
     */
    public function getNormsCount()
    {
        return count($this->norms);
    }

    /**
     * Zwraca tekstową reprezentację typu parametru
     */
    public function getTypeName()
    {
        $types = self::getTypeOptions();
        return $types[$this->type] ?? $this->type;
    }

    /**
     * Sprawdza czy parametr jest typu numerycznego
     */
    public function isNumeric()
    {
        return in_array($this->type, [
            self::TYPE_RANGE,
            self::TYPE_SINGLE_THRESHOLD,
            self::TYPE_MULTIPLE_THRESHOLDS,
            self::TYPE_NUMERIC
        ]);
    }

    /**
     * Zwraca opis parametru z jednostką
     */
    public function getFullDescription()
    {
        $desc = $this->name;
        
        if ($this->unit) {
            $desc .= ' (' . $this->unit . ')';
        }
        
        if (isset($this->description) && $this->description) {
            $desc .= ' - ' . $this->description;
        }
        
        return $desc;
    }

    /**
     * Przed zapisem - ustaw kolejność jeśli nie ustawiona
     */
    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            if ($insert && ($this->order_index === null || $this->order_index === 0)) {
                // Znajdź największą kolejność w szablonie
                $maxOrder = self::find()
                    ->where(['test_template_id' => $this->test_template_id])
                    ->max('order_index');
                
                $this->order_index = ($maxOrder ?: 0) + 1;
            }
            
            return true;
        }
        return false;
    }

    /**
     * Zmiana kolejności parametru
     */
    public function changeOrder($newOrder)
    {
        $oldOrder = $this->order_index;
        
        if ($oldOrder == $newOrder) {
            return true;
        }
        
        $transaction = self::getDb()->beginTransaction();
        
        try {
            if ($newOrder > $oldOrder) {
                // Przesuń w dół
                self::updateAll(
                    ['order_index' => new \yii\db\Expression('order_index - 1')],
                    [
                        'and',
                        ['test_template_id' => $this->test_template_id],
                        ['>', 'order_index', $oldOrder],
                        ['<=', 'order_index', $newOrder]
                    ]
                );
            } else {
                // Przesuń w górę
                self::updateAll(
                    ['order_index' => new \yii\db\Expression('order_index + 1')],
                    [
                        'and',
                        ['test_template_id' => $this->test_template_id],
                        ['>=', 'order_index', $newOrder],
                        ['<', 'order_index', $oldOrder]
                    ]
                );
            }
            
            $this->order_index = $newOrder;
            $this->save(false);
            
            $transaction->commit();
            return true;
        } catch (\Exception $e) {
            $transaction->rollBack();
            return false;
        }
    }

    /**
     * Zwraca następny parametr w kolejności
     */
    public function getNext()
    {
        return self::find()
            ->where([
                'test_template_id' => $this->test_template_id,
                '>', 'order_index', $this->order_index
            ])
            ->orderBy('order_index ASC')
            ->one();
    }

    /**
     * Zwraca poprzedni parametr w kolejności
     */
    public function getPrevious()
    {
        return self::find()
            ->where([
                'test_template_id' => $this->test_template_id,
                '<', 'order_index', $this->order_index
            ])
            ->orderBy('order_index DESC')
            ->one();
    }

    /**
     * Sprawdza czy można usunąć parametr
     */
    public function canDelete()
    {
        // Sprawdź czy są wyniki badań
        $resultsCount = ResultValue::find()
            ->where(['parameter_id' => $this->id])
            ->count();
        
        return $resultsCount == 0;
    }

    /**
     * Usuwa parametr wraz z normami (jeśli można)
     */
    public function deleteWithNorms()
    {
        if (!$this->canDelete()) {
            return false;
        }
        
        $transaction = self::getDb()->beginTransaction();
        
        try {
            // Usuń normy
            ParameterNorm::deleteAll(['parameter_id' => $this->id]);
            
            // Usuń parametr
            $result = $this->delete();
            
            if ($result) {
                // Przeindeksuj kolejność
                self::updateAll(
                    ['order_index' => new \yii\db\Expression('order_index - 1')],
                    [
                        'and',
                        ['test_template_id' => $this->test_template_id],
                        ['>', 'order_index', $this->order_index]
                    ]
                );
                
                $transaction->commit();
                return true;
            } else {
                $transaction->rollBack();
                return false;
            }
        } catch (\Exception $e) {
            $transaction->rollBack();
            return false;
        }
    }
}