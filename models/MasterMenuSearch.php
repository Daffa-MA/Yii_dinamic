<?php
namespace app\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\MasterMenu;

class MasterMenuSearch extends MasterMenu
{
    public function rules()
    {
        return [
            [['id', 'parent_id', 'page_id', 'sort_order', 'is_active', 'created_at', 'updated_at'], 'integer'],
            [['name', 'icon'], 'safe'],
        ];
    }

    public function scenarios()
    {
        return Model::scenarios();
    }

    public function search($params)
    {
        $query = MasterMenu::find()
            ->andWhere(['status' => 1])
            ->orderBy(['sort_order' => SORT_ASC, 'name' => SORT_ASC]);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => false,
            'pagination' => false,
        ]);

        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }

        $query->andFilterWhere(['like', 'name', $this->name]);

        return $dataProvider;
    }
}