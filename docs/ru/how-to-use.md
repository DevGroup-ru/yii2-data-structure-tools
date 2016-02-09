# Использование свойств из DataStructureTools для произвольной модели

Ниже будет описан процесс (он состоит из трех простых шагов) добавления возможности прикреплять свойства к модели на примере `app\models\Page`. 


## Генерация необходимых таблиц

В первую очередь, необходимо создать необходимые для хранения свойств таблицы (они разные для каждой модели). Для этого есть специальнообученный хелпер.

Ниже описана команда позволяющая создать необходимые таблицы для модели `app\models\Page`:

```php
\DevGroup\DataStructure\helpers\PropertiesTableGenerator::generate('\app\models\Page');
// \DevGroup\DataStructure\helpers\PropertiesTableGenerator::generate(\app\models\Page::class); // Альтернативная версия
```

Как правило, такие операции проводятся в миграциях.


## Модификация модели

Когда таблицы созданы, необходимо добавить к модели поведение. Для этого открываем файл нашей модели и добавляем в метод `behaviors()` поведение `\DevGroup\DataStructure\behaviors\HasProperties`. Получится что-то похожее на 

```php
public function behaviors()
{
    return [
        // other behaviors
        'properties' => [
            'class' => '\DevGroup\DataStructure\behaviors\HasProperties',
            // 'class' => \DevGroup\DataStructure\behaviors\HasProperties::class, // Альтернативная версия
            'autoFetchProperties' => true,
        ],
        // other behaviors
    ];
}
```

Следом добавляем треит `\DevGroup\DataStructure\traits\PropertiesTrait`

```php
// uses namespace and uses
class Page extends \yii\db\ActiveRecord
{
    use \DevGroup\DataStructure\traits\PropertiesTrait;
    // other code here
}
```

И добавим правила валидации полей свойств. Для этого модифицируем метод `rules()` нашей модели:

```php
public function rules()
{
    return ArrayHelper::merge(
        [
            // Page model rules
        ],
        $this->propertiesRules()
    );
}
```

Теперь у нас появилась возможность получать группы и свойста, валидировать данные и сохранять.


## Редактирование свойств в backend

Для редактирования свойств нам необходимо немного изменить экшен редактирования контроллера административной части, добавив в него строку с установкой флага автосохранения свойств модели `$model->autoSaveProperties = true;` сразу после загрузки модели.

```php
public function actionUpdate($id)
{
    $model = $this->findModel($id);
    $model->autoSaveProperties = true;
    // other code
}
```

После этого добавляем вызов виджета формы свойств в представлении формы редактирования модели.

```php
<?=
\DevGroup\DataStructure\widgets\PropertiesForm::widget(
    [
        'model' => $model,
    ]
)
?>
```

На этом все. Теперь можно редактировать свойства прикрепленные к модели `app\models\Page`.
