# back.form
`Bitrix component fei:back.form`

Компонент формы обратной связи с поддержкой CRM работает через Инфоблоки, почтовые, и системные события

**Поддерживает**
- Генерирует форму (данные для формы на основании свойств инфоблока)
- Запись в Инфоблок результатов формы
- Запись в ЦРМ как лид (адаптации для малых редакций интеграции с CRM)
- Событие успешной отправки (`[main, FeiOnBackFormSubmit]` возможно повесить свой доп обработчик, например для Rest API метода)
- Отправка базовым методом через почтовое событие писем (`CEvent::SendImmediate`) - регулируются через параметры компонента

```` 
use Bitrix\Main\EventManager;

$handler = EventManager::getInstance()->addEventHandler(
    "main",
    "FeiOnBackFormSubmit",
    function (Bitrix\Main\Event $event) {
        $arParameters = $event->getParameters();
        return new \Bitrix\Main\EventResult(
            \Bitrix\Main\EventResult::SUCCESS,
            ['sent' => 'Y'],
            'main',
            $event
        );
    }
);
````