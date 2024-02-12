<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\QuizAnswer;
use App\Library\AppLocale;

class quizAnswersWithInitData extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $appLocale = AppLocale::getInstance();

        /* 1. 'What is HTTP middleware?', */
        $quizAnswer             = new QuizAnswer;
        $quizAnswer->quiz_id    = 1;
        $quizAnswer->is_correct = true;
        $quizAnswer->setTranslation(
            'text',
            $appLocale::APP_LOCALE_ENGLISH,
            'HTTP middleware is a technique for filtering HTTP requests'
        );
        $quizAnswer->setTranslation(
            'text',
            $appLocale::APP_LOCALE_UKRAINIAN,
            'Проміжне програмне забезпечення HTTP — це техніка для фільтрації запитів HTTP'
        );
        $quizAnswer->setTranslation(
            'text',
            $appLocale::APP_LOCALE_SPANISH,
            'El middleware HTTP es una técnica para filtrar solicitudes HTTP'
        );
        $quizAnswer->save();

        $quizAnswer             = new QuizAnswer;
        $quizAnswer->quiz_id    = 1;
        $quizAnswer->is_correct = false;
        $quizAnswer->setTranslation('text', $appLocale::APP_LOCALE_ENGLISH, 'Library to work with configuration files');
        $quizAnswer->setTranslation(
            'text',
            $appLocale::APP_LOCALE_UKRAINIAN,
            'Бібліотека для роботи з конфігураційними файлами'
        );
        $quizAnswer->setTranslation(
            'text',
            $appLocale::APP_LOCALE_SPANISH,
            'Biblioteca para trabajar con archivos de configuración'
        );
        $quizAnswer->save();

        $quizAnswer             = new QuizAnswer;
        $quizAnswer->quiz_id    = 1;
        $quizAnswer->is_correct = false;
        $quizAnswer->setTranslation(
            'text',
            $appLocale::APP_LOCALE_ENGLISH,
            'A tool used for performing dependency injection in Laravel'
        );
        $quizAnswer->setTranslation(
            'text',
            $appLocale::APP_LOCALE_UKRAINIAN,
            'Інструмент, який використовується для впровадження залежностей у Laravel'
        );
        $quizAnswer->setTranslation(
            'text',
            $appLocale::APP_LOCALE_SPANISH,
            'Una herramienta utilizada para realizar inyección de dependencia en Laravel'
        );
        $quizAnswer->save();

        /* 1. 'What is HTTP middleware?' END */


        /* 2. 'What does ORM stand for?', */
        $quizAnswer             = new QuizAnswer;
        $quizAnswer->quiz_id    = 2;
        $quizAnswer->is_correct = false;
        $quizAnswer->setTranslation(
            'text',
            $appLocale::APP_LOCALE_ENGLISH,
            'ORM stands for Object Related Matter'
        );
        $quizAnswer->setTranslation(
            'text',
            $appLocale::APP_LOCALE_UKRAINIAN,
            'ORM означає Object Related Matter'
        );
        $quizAnswer->setTranslation(
            'text',
            $appLocale::APP_LOCALE_SPANISH,
            'ORM significa materia relacionada con objetos'
        );
        $quizAnswer->save();

        $quizAnswer             = new QuizAnswer;
        $quizAnswer->quiz_id    = 2;
        $quizAnswer->is_correct = true;
        $quizAnswer->setTranslation(
            'text',
            $appLocale::APP_LOCALE_ENGLISH,
            'ORM stands for Object Relational Mapping'
        );
        $quizAnswer->setTranslation(
            'text',
            $appLocale::APP_LOCALE_UKRAINIAN,
            'ORM означає Object Relational Mapping'
        );
        $quizAnswer->setTranslation(
            'text',
            $appLocale::APP_LOCALE_SPANISH,
            'ORM significa mapeo relacional de objetos'
        );
        $quizAnswer->save();

        $quizAnswer             = new QuizAnswer;
        $quizAnswer->quiz_id    = 2;
        $quizAnswer->is_correct = false;
        $quizAnswer->setTranslation(
            'text',
            $appLocale::APP_LOCALE_ENGLISH,
            'ORM stands for Offer Run Master'
        );
        $quizAnswer->setTranslation(
            'text',
            $appLocale::APP_LOCALE_UKRAINIAN,
            'ORM означає Пропозиція Run Master'
        );
        $quizAnswer->setTranslation(
            'text',
            $appLocale::APP_LOCALE_SPANISH,
            'ORM significa Oferta Run Master'
        );
        $quizAnswer->save();

        /* 2. 'What does ORM stand for?' END */


        /* 3. 'How can you generate URLs?', */

        $quizAnswer             = new QuizAnswer;
        $quizAnswer->quiz_id    = 3;
        $quizAnswer->is_correct = false;
        $quizAnswer->setTranslation(
            'text',
            $appLocale::APP_LOCALE_ENGLISH,
            'With external library UrlCreatorPlugin'
        );
        $quizAnswer->setTranslation(
            'text',
            $appLocale::APP_LOCALE_UKRAINIAN,
            'Із зовнішньою бібліотекою UrlCreatorPlugin'
        );
        $quizAnswer->setTranslation(
            'text',
            $appLocale::APP_LOCALE_SPANISH,
            'Con biblioteca externa UrlCreatorPlugin'
        );
        $quizAnswer->save();

        $quizAnswer             = new QuizAnswer;
        $quizAnswer->quiz_id    = 3;
        $quizAnswer->is_correct = false;
        $quizAnswer->setTranslation(
            'text',
            $appLocale::APP_LOCALE_ENGLISH,
            'Using UrlBuilder class'
        );
        $quizAnswer->setTranslation(
            'text',
            $appLocale::APP_LOCALE_UKRAINIAN,
            'Використання класу UrlBuilder'
        );
        $quizAnswer->setTranslation(
            'text',
            $appLocale::APP_LOCALE_SPANISH,
            'Usando la clase UrlBuilder'
        );
        $quizAnswer->save();

        $quizAnswer             = new QuizAnswer;
        $quizAnswer->quiz_id    = 3;
        $quizAnswer->is_correct = true;
        $quizAnswer->setTranslation(
            'text',
            $appLocale::APP_LOCALE_ENGLISH,
            'Laravel has helpers to generate URLs.'
        );
        $quizAnswer->setTranslation(
            'text',
            $appLocale::APP_LOCALE_UKRAINIAN,
            'Laravel має помічники для створення URL-адрес.'
        );
        $quizAnswer->setTranslation(
            'text',
            $appLocale::APP_LOCALE_SPANISH,
            'Laravel tiene ayudantes para generar URL.'
        );
        $quizAnswer->save();

        /* 3. 'How can you generate URLs?' END */


        /* 4. 'What is query scope' START */

        $quizAnswer             = new QuizAnswer;
        $quizAnswer->quiz_id    = 4;
        $quizAnswer->is_correct = true;
        $quizAnswer->setTranslation(
            'text',
            $appLocale::APP_LOCALE_ENGLISH,
            'It is a feature of Laravel where we can reuse similar queries. We do not require to write the same types of queries again in the Laravel project. Once the scope is defined, just call the scope method when querying the model.'
        );
        $quizAnswer->setTranslation(
            'text',
            $appLocale::APP_LOCALE_UKRAINIAN,
            'Це функція Laravel, де ми можемо повторно використовувати подібні запити. Ми не вимагаємо повторного написання тих самих типів запитів у проекті Laravel. Коли область визначена, просто викличте метод області під час запиту моделі.'
        );
        $quizAnswer->setTranslation(
            'text',
            $appLocale::APP_LOCALE_SPANISH,
            'Es una característica de Laravel donde podemos reutilizar consultas similares. No es necesario volver a escribir los mismos tipos de consultas en el proyecto Laravel. Una vez que se define el alcance, simplemente llame al método de alcance cuando consulte el modelo.'
        );
        $quizAnswer->save();


        $quizAnswer             = new QuizAnswer;
        $quizAnswer->quiz_id    = 4;
        $quizAnswer->is_correct = false;
        $quizAnswer->setTranslation(
            'text',
            $appLocale::APP_LOCALE_ENGLISH,
            'It is a feature of Laravel where model can be assigned to other model with Has many relation.'
        );
        $quizAnswer->setTranslation(
            'text',
            $appLocale::APP_LOCALE_UKRAINIAN,
            'Це функція Laravel, де модель можна призначити іншій моделі з багатьма зв’язками.'
        );
        $quizAnswer->setTranslation(
            'text',
            $appLocale::APP_LOCALE_SPANISH,
            'Es una característica de Laravel donde el modelo se puede asignar a otro modelo con muchas relaciones.'
        );
        $quizAnswer->save();
        $quizAnswer             = new QuizAnswer;
        $quizAnswer->quiz_id    = 4;
        $quizAnswer->is_correct = false;
        $quizAnswer->setTranslation(
            'text',
            $appLocale::APP_LOCALE_ENGLISH,
            'It is a feature of Laravel where model is redeclared with some .'
        );
        $quizAnswer->setTranslation(
            'text',
            $appLocale::APP_LOCALE_UKRAINIAN,
            'Це функція Laravel, де модель можна призначити іншій моделі з багатьма зв’язками.'
        );
        $quizAnswer->setTranslation(
            'text',
            $appLocale::APP_LOCALE_SPANISH,
            'Es una característica de Laravel donde el modelo se puede asignar a otro modelo con muchas relaciones.'
        );
        $quizAnswer->save();

        /* 4. 'What is query scope' END */


        /* 5. 'What is namespace in Laravel ?' START */

        $quizAnswer             = new QuizAnswer;
        $quizAnswer->quiz_id    = 5;
        $quizAnswer->is_correct = false;
        $quizAnswer->setTranslation(
            'text',
            $appLocale::APP_LOCALE_ENGLISH,
            'A namespace allows a user define personal name in plugins development.'
        );
        $quizAnswer->setTranslation(
            'text',
            $appLocale::APP_LOCALE_UKRAINIAN,
            'Простір імен дозволяє користувачеві визначати особисте ім’я під час розробки плагінів.'
        );
        $quizAnswer->setTranslation(
            'text',
            $appLocale::APP_LOCALE_SPANISH,
            'Un espacio de nombres permite que un usuario defina un nombre personal en el desarrollo de complementos.'
        );
        $quizAnswer->save();


        $quizAnswer             = new QuizAnswer;
        $quizAnswer->quiz_id    = 5;
        $quizAnswer->is_correct = true;
        $quizAnswer->setTranslation(
            'text',
            $appLocale::APP_LOCALE_ENGLISH,
            'A namespace allows a user to group the functions, classes, and constants under a specific name.'
        );
        $quizAnswer->setTranslation(
            'text',
            $appLocale::APP_LOCALE_UKRAINIAN,
            'Простір імен дозволяє користувачеві групувати функції, класи та константи під певним іменем.'
        );
        $quizAnswer->setTranslation(
            'text',
            $appLocale::APP_LOCALE_SPANISH,
            'A namespace allows a user to group the functions, classes, and constants under a specific name.'
        );
        $quizAnswer->save();


        $quizAnswer             = new QuizAnswer;
        $quizAnswer->quiz_id    = 5;
        $quizAnswer->is_correct = false;
        $quizAnswer->setTranslation(
            'text',
            $appLocale::APP_LOCALE_ENGLISH,
            'A namespace is not used in Laravel development.'
        );
        $quizAnswer->setTranslation(
            'text',
            $appLocale::APP_LOCALE_UKRAINIAN,
            'Простір імен не використовується в розробці Laravel.'
        );
        $quizAnswer->setTranslation(
            'text',
            $appLocale::APP_LOCALE_SPANISH,
            'Un espacio de nombres no se usa en el desarrollo de Laravel.'
        );
        $quizAnswer->save();


        /* 5. 'What is namespace in Laravel ?' END */


        /* 6. '        $quiz_category_id = 2; => Vuejs development knowledge ?' Start */

        $quizAnswer             = new QuizAnswer;
        $quizAnswer->quiz_id    = 6;
        $quizAnswer->is_correct = false;
        $quizAnswer->setTranslation(
            'text',
            $appLocale::APP_LOCALE_ENGLISH,
            '<slot> is an element used for importing expernal plugin.'
        );
        $quizAnswer->setTranslation(
            'text',
            $appLocale::APP_LOCALE_UKRAINIAN,
            '<slot> — це елемент, який використовується для імпорту зовнішнього плагіна.'
        );
        $quizAnswer->setTranslation(
            'text',
            $appLocale::APP_LOCALE_SPANISH,
            '<slot> es un elemento que se utiliza para importar un complemento externo.'
        );
        $quizAnswer->save();

        $quizAnswer             = new QuizAnswer;
        $quizAnswer->quiz_id    = 6;
        $quizAnswer->is_correct = true;
        $quizAnswer->setTranslation(
            'text',
            $appLocale::APP_LOCALE_ENGLISH,
            'Developers familiar with Vue.js should understand that <slot> is an element that programmers use as a content distribution outlet.'
        );
        $quizAnswer->setTranslation(
            'text',
            $appLocale::APP_LOCALE_UKRAINIAN,
            'Розробники, знайомі з Vue.js, повинні розуміти, що <slot> — це елемент, який програмісти використовують як розповсюдження вмісту.'
        );
        $quizAnswer->setTranslation(
            'text',
            $appLocale::APP_LOCALE_SPANISH,
            'Los desarrolladores familiarizados con Vue.js deben entender que <slot> es un elemento que los programadores usan como medio de distribución de contenido.'
        );
        $quizAnswer->save();

        $quizAnswer             = new QuizAnswer;
        $quizAnswer->quiz_id    = 6;
        $quizAnswer->is_correct = false;
        $quizAnswer->setTranslation(
            'text',
            $appLocale::APP_LOCALE_ENGLISH,
            'Element <slot> is used for using frame-html element'
        );
        $quizAnswer->setTranslation(
            'text',
            $appLocale::APP_LOCALE_UKRAINIAN,
            'Елемент <slot> використовується для використання елемента frame-html'
        );
        $quizAnswer->setTranslation(
            'text',
            $appLocale::APP_LOCALE_SPANISH,
            'El elemento <ranura> se usa para usar el elemento frame-html'
        );
        $quizAnswer->save();

        /* 6. '        $quiz_category_id = 2; => Vuejs development knowledge ?' END */


        /* 7. '        $quiz_category_id = 2; => 'What are mixins?' START */

        $quizAnswer             = new QuizAnswer;
        $quizAnswer->quiz_id    = 7;
        $quizAnswer->is_correct = false;
        $quizAnswer->setTranslation(
            'text',
            $appLocale::APP_LOCALE_ENGLISH,
            'Mixins are tools for debugging in chrome browser.'
        );
        $quizAnswer->setTranslation(
            'text',
            $appLocale::APP_LOCALE_UKRAINIAN,
            'Міксини — це інструменти для налагодження в браузері Chrome.'
        );
        $quizAnswer->setTranslation(
            'text',
            $appLocale::APP_LOCALE_SPANISH,
            'Los mixins son herramientas para depurar en el navegador Chrome.'
        );
        $quizAnswer->save();

        $quizAnswer             = new QuizAnswer;
        $quizAnswer->quiz_id    = 7;
        $quizAnswer->is_correct = false;
        $quizAnswer->setTranslation(
            'text',
            $appLocale::APP_LOCALE_ENGLISH,
            'Mixins is external library for axios request supporting.'
        );
        $quizAnswer->setTranslation(
            'text',
            $appLocale::APP_LOCALE_UKRAINIAN,
            'Mixins — зовнішня бібліотека для підтримки запитів axios.'
        );
        $quizAnswer->setTranslation(
            'text',
            $appLocale::APP_LOCALE_SPANISH,
            'Mixins es una biblioteca externa para soporte de solicitudes de axios.'
        );
        $quizAnswer->save();


        $quizAnswer             = new QuizAnswer;
        $quizAnswer->quiz_id    = 7;
        $quizAnswer->is_correct = true;
        $quizAnswer->setTranslation(
            'text',
            $appLocale::APP_LOCALE_ENGLISH,
            'Mixins are a versatile approach to sharing reusable Vue component functionality.'
        );
        $quizAnswer->setTranslation(
            'text',
            $appLocale::APP_LOCALE_UKRAINIAN,
            'Міксини — це універсальний підхід до спільного використання багаторазових функцій компонентів Vue.'
        );
        $quizAnswer->setTranslation(
            'text',
            $appLocale::APP_LOCALE_SPANISH,
            'Los mixins son un enfoque versátil para compartir la funcionalidad de componentes reutilizables de Vue.'
        );
        $quizAnswer->save();

        /* 7. '        $quiz_category_id = 2; => 'What are mixins?' END */


        /* 8. '        $quiz_category_id = 2; => 'What are watchers?' START */

        $quizAnswer             = new QuizAnswer;
        $quizAnswer->quiz_id    = 8;
        $quizAnswer->is_correct = false;
        $quizAnswer->setTranslation(
            'text',
            $appLocale::APP_LOCALE_ENGLISH,
            'Watchers are multi-dimensional arrays declared in constructor of vue component'
        );
        $quizAnswer->setTranslation(
            'text',
            $appLocale::APP_LOCALE_UKRAINIAN,
            'Спостерігачі(Watchers) — це багатовимірні масиви, оголошені в конструкторі компонента vue'
        );
        $quizAnswer->setTranslation(
            'text',
            $appLocale::APP_LOCALE_SPANISH,
            'Los observadores son matrices multidimensionales declaradas en el constructor del componente vue'
        );
        $quizAnswer->save();

        $quizAnswer             = new QuizAnswer;
        $quizAnswer->quiz_id    = 8;
        $quizAnswer->is_correct = true;
        $quizAnswer->setTranslation(
            'text',
            $appLocale::APP_LOCALE_ENGLISH,
            'Instead of utilising computed properties, watchers are a more generic way to react to data changes'
        );
        $quizAnswer->setTranslation(
            'text',
            $appLocale::APP_LOCALE_UKRAINIAN,
            'Замість використання обчислених властивостей спостерігачі є більш загальним способом реагування на зміни даних'
        );
        $quizAnswer->setTranslation(
            'text',
            $appLocale::APP_LOCALE_SPANISH,
            'En lugar de utilizar propiedades calculadas, los observadores son una forma más genérica de reaccionar a los cambios de datos.'
        );
        $quizAnswer->save();

        /* 8. '        $quiz_category_id = 2; => 'What are watchers?' END */


        /* 9. '        $quiz_category_id = 3; => 'What is the use of isNaN function?' START */

        $quizAnswer             = new QuizAnswer;
        $quizAnswer->quiz_id    = 9;
        $quizAnswer->is_correct = false;
        $quizAnswer->setTranslation(
            'text',
            $appLocale::APP_LOCALE_ENGLISH,
            'isNan function returns true if the argument is not multi-array value.'
        );
        $quizAnswer->setTranslation(
            'text',
            $appLocale::APP_LOCALE_UKRAINIAN,
            'Функція isNan повертає значення true, якщо аргумент не є значенням із кількох масивів.'
        );
        $quizAnswer->setTranslation(
            'text',
            $appLocale::APP_LOCALE_SPANISH,
            'La función isNan devuelve verdadero si el argumento no es un valor de matriz múltiple.'
        );
        $quizAnswer->save();

        $quizAnswer             = new QuizAnswer;
        $quizAnswer->quiz_id    = 9;
        $quizAnswer->is_correct = true;
        $quizAnswer->setTranslation(
            'text',
            $appLocale::APP_LOCALE_ENGLISH,
            'isNan function returns true if the argument is not a number; otherwise, it is false.'
        );
        $quizAnswer->setTranslation(
            'text',
            $appLocale::APP_LOCALE_UKRAINIAN,
            'Функція isNan повертає істину, якщо аргумент не є числом; в іншому випадку це невірно.'
        );
        $quizAnswer->setTranslation(
            'text',
            $appLocale::APP_LOCALE_SPANISH,
            'la función isNan devuelve verdadero si el argumento no es un número; de lo contrario, es falso.'
        );
        $quizAnswer->save();

        $quizAnswer             = new QuizAnswer;
        $quizAnswer->quiz_id    = 9;
        $quizAnswer->is_correct = false;
        $quizAnswer->setTranslation(
            'text',
            $appLocale::APP_LOCALE_ENGLISH,
            'isNan function returns true reference to the Nan library.'
        );
        $quizAnswer->setTranslation(
            'text',
            $appLocale::APP_LOCALE_UKRAINIAN,
            'Функція isNan повертає справжнє посилання на бібліотеку Nan.'
        );
        $quizAnswer->setTranslation(
            'text',
            $appLocale::APP_LOCALE_SPANISH,
            'La función isNan devuelve una referencia verdadera a la biblioteca Nan.'
        );
        $quizAnswer->save();

        /* 9. '        $quiz_category_id = 3; => 'What is the use of isNaN function?' END */


        /* 10. '        $quiz_category_id = 3; => 'What is ‘this’ keyword in JavaScript?' START */

        $quizAnswer             = new QuizAnswer;
        $quizAnswer->quiz_id    = 10;
        $quizAnswer->is_correct = true;
        $quizAnswer->setTranslation(
            'text',
            $appLocale::APP_LOCALE_ENGLISH,
            '‘This’ keyword refers to the object from where it was called.'
        );
        $quizAnswer->setTranslation(
            'text',
            $appLocale::APP_LOCALE_UKRAINIAN,
            'Ключове слово «This» відноситься до об’єкта, звідки його було викликано.'
        );
        $quizAnswer->setTranslation(
            'text',
            $appLocale::APP_LOCALE_SPANISH,
            'La palabra clave \'This\' se refiere al objeto desde donde se llamó.'
        );
        $quizAnswer->save();


        $quizAnswer             = new QuizAnswer;
        $quizAnswer->quiz_id    = 10;
        $quizAnswer->is_correct = false;
        $quizAnswer->setTranslation(
            'text',
            $appLocale::APP_LOCALE_ENGLISH,
            '‘This’ keyword is a way to convert javascript code into Java code.'
        );
        $quizAnswer->setTranslation(
            'text',
            $appLocale::APP_LOCALE_UKRAINIAN,
            '‘This’ keyword is a way to convert javascript code into Java code.'
        );
        $quizAnswer->setTranslation(
            'text',
            $appLocale::APP_LOCALE_SPANISH,
            'La palabra clave \'This\' es una forma de convertir código javascript en código Java.'
        );
        $quizAnswer->save();


        $quizAnswer             = new QuizAnswer;
        $quizAnswer->quiz_id    = 10;
        $quizAnswer->is_correct = false;
        $quizAnswer->setTranslation(
            'text',
            $appLocale::APP_LOCALE_ENGLISH,
            '‘This’ keyword referef to parent object in dom structure'
        );
        $quizAnswer->setTranslation(
            'text',
            $appLocale::APP_LOCALE_UKRAINIAN,
            'Ключове слово «This» посилається на батьківський об’єкт у структурі dom'
        );
        $quizAnswer->setTranslation(
            'text',
            $appLocale::APP_LOCALE_SPANISH,
            'La palabra clave \'This\' se refiere al objeto principal en la estructura dom'
        );
        $quizAnswer->save();

        /* 10. '        $quiz_category_id = 3; => 'What is ‘this’ keyword in JavaScript?' END */


    }
}
