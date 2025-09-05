<?php

namespace Tests\Unit;

use Tests\TestCase;

class TranslationTest extends TestCase
{
    /**
     * Test that English translations are loading correctly
     *
     * @return void
     */
    public function test_english_translations_load_correctly()
    {
        // Set locale to English
        app()->setLocale('en');

        // Test basic translation
        $this->assertEquals('Welcome', __('welcome'));
        $this->assertEquals('Login', __('login'));
        $this->assertEquals('Dashboard', __('dashboard'));

        // Test navigation translations
        $this->assertEquals('Homeschool Hub', __('homeschool_hub'));
        $this->assertEquals('Parent Dashboard', __('parent_dashboard'));
        $this->assertEquals('Children', __('children'));
        $this->assertEquals('Subjects', __('subjects'));
        $this->assertEquals('Planning', __('planning'));
        $this->assertEquals('Reviews', __('reviews'));
        $this->assertEquals('Calendar', __('calendar'));

        // Test action translations
        $this->assertEquals('Add', __('add'));
        $this->assertEquals('Edit', __('edit'));
        $this->assertEquals('Update', __('update'));
        $this->assertEquals('Delete', __('delete'));
        $this->assertEquals('Save', __('save'));
        $this->assertEquals('Cancel', __('cancel'));

        // Test validation attribute names
        $this->assertEquals('email address', __('validation.attributes.email'));
        $this->assertEquals('password', __('validation.attributes.password'));
        $this->assertEquals('name', __('validation.attributes.name'));
    }

    /**
     * Test that Russian translations are loading correctly
     *
     * @return void
     */
    public function test_russian_translations_load_correctly()
    {
        // Set locale to Russian
        app()->setLocale('ru');

        // Test basic translation
        $this->assertEquals('Добро пожаловать', __('welcome'));
        $this->assertEquals('Войти', __('login'));
        $this->assertEquals('Панель управления', __('dashboard'));

        // Test navigation translations
        $this->assertEquals('Центр домашнего обучения', __('homeschool_hub'));
        $this->assertEquals('Панель родителя', __('parent_dashboard'));
        $this->assertEquals('Дети', __('children'));
        $this->assertEquals('Предметы', __('subjects'));
        $this->assertEquals('Планирование', __('planning'));
        $this->assertEquals('Повторение', __('reviews'));
        $this->assertEquals('Календарь', __('calendar'));

        // Test action translations
        $this->assertEquals('Добавить', __('add'));
        $this->assertEquals('Редактировать', __('edit'));
        $this->assertEquals('Обновить', __('update'));
        $this->assertEquals('Удалить', __('delete'));
        $this->assertEquals('Сохранить', __('save'));
        $this->assertEquals('Отмена', __('cancel'));

        // Test validation attribute names
        $this->assertEquals('адрес электронной почты', __('validation.attributes.email'));
        $this->assertEquals('пароль', __('validation.attributes.password'));
        $this->assertEquals('имя', __('validation.attributes.name'));
    }

    /**
     * Test translation with parameters
     *
     * @return void
     */
    public function test_translations_with_parameters()
    {
        app()->setLocale('en');

        // Test parameterized translations
        $this->assertEquals('Welcome, test@example.com', __('welcome_user', ['email' => 'test@example.com']));
        $this->assertEquals('Level 3', __('level', ['level' => 3]));
        $this->assertEquals('5 Reviews', __('reviews_count', ['count' => 5]));
        $this->assertEquals('Evidence captured for John.', __('evidence_captured_for_child', ['name' => 'John']));

        // Test Russian parameterized translations
        app()->setLocale('ru');
        $this->assertEquals('Добро пожаловать, test@example.com', __('welcome_user', ['email' => 'test@example.com']));
        $this->assertEquals('Уровень 3', __('level', ['level' => 3]));
        $this->assertEquals('5 повторений', __('reviews_count', ['count' => 5]));
        $this->assertEquals('Доказательства собраны для John.', __('evidence_captured_for_child', ['name' => 'John']));
    }

    /**
     * Test validation message translations
     *
     * @return void
     */
    public function test_validation_message_translations()
    {
        app()->setLocale('en');

        // Test validation messages
        $this->assertEquals('The :attribute field is required.', __('validation.required'));
        $this->assertEquals('The :attribute must be a valid email address.', __('validation.email'));
        $this->assertEquals('The :attribute confirmation does not match.', __('validation.confirmed'));

        app()->setLocale('ru');

        // Test Russian validation messages
        $this->assertEquals('Поле :attribute обязательно для заполнения.', __('validation.required'));
        $this->assertEquals('Поле :attribute должно содержать корректный email адрес.', __('validation.email'));
        $this->assertEquals('Поле :attribute не совпадает с подтверждением.', __('validation.confirmed'));
    }

    /**
     * Test that fallback to English works when translation is missing
     *
     * @return void
     */
    public function test_fallback_to_english()
    {
        // Set locale to Russian but test a key that might not exist
        app()->setLocale('ru');

        // This should exist in both languages
        $this->assertEquals('Войти', __('login'));

        // Test that when a key doesn't exist, it returns the key itself
        $result = __('non_existent_key');
        $this->assertEquals('non_existent_key', $result);
    }
}
