<?php

namespace App\Tests\Form;

use App\Entity\FoundsImage;
use App\Form\FoundsImageUploadType;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class FoundsImageUploadTypeTest extends TypeTestCase
{
    public function testSubmitValidData(): void
    {
        $formData = [
            'name' => 'Test Image',
            'note' => 'Test note',
            'isPublic' => true,
        ];

        $form = $this->factory->create(FoundsImageUploadType::class);
        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        $this->assertTrue($form->isValid());

        $view = $form->createView();
        $children = $view->children;

        $this->assertArrayHasKey('name', $children);
        $this->assertArrayHasKey('note', $children);
        $this->assertArrayHasKey('isPublic', $children);
    }

    public function testSubmitEmptyData(): void
    {
        $form = $this->factory->create(FoundsImageUploadType::class);
        $form->submit([]);

        $this->assertTrue($form->isSynchronized());
        $this->assertTrue($form->isValid());
    }

    public function testSubmitInvalidData(): void
    {
        $formData = [
            'name' => '', // Leerer Name sollte ungültig sein
            'note' => 'Test note',
            'isPublic' => true,
        ];

        $form = $this->factory->create(FoundsImageUploadType::class);
        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        $this->assertFalse($form->isValid());
        $this->assertTrue($form->get('name')->getErrors()->count() > 0);
    }

    public function testFormFields(): void
    {
        $form = $this->factory->create(FoundsImageUploadType::class);
        $view = $form->createView();

        $this->assertArrayHasKey('name', $view->children);
        $this->assertArrayHasKey('note', $view->children);
        $this->assertArrayHasKey('isPublic', $view->children);
    }

    public function testFormConfiguration(): void
    {
        $form = $this->factory->create(FoundsImageUploadType::class);
        $view = $form->createView();

        // Test name field
        $nameField = $view->children['name'];
        $this->assertEquals('text', $nameField->vars['block_prefixes'][1]);

        // Test note field
        $noteField = $view->children['note'];
        $this->assertEquals('textarea', $noteField->vars['block_prefixes'][1]);

        // Test isPublic field
        $isPublicField = $view->children['isPublic'];
        $this->assertEquals('checkbox', $isPublicField->vars['block_prefixes'][1]);
    }

    public function testFormDataTransformation(): void
    {
        $form = $this->factory->create(FoundsImageUploadType::class);
        
        $formData = [
            'name' => 'Test Image Name',
            'note' => 'Test note content',
            'isPublic' => true,
        ];

        $form->submit($formData);

        $this->assertTrue($form->isValid());
        $this->assertEquals('Test Image Name', $form->get('name')->getData());
        $this->assertEquals('Test note content', $form->get('note')->getData());
        $this->assertTrue($form->get('isPublic')->getData());
    }

    public function testFormValidation(): void
    {
        $form = $this->factory->create(FoundsImageUploadType::class);
        
        // Test mit zu langem Namen
        $formData = [
            'name' => str_repeat('a', 256), // Zu lang
            'note' => 'Test note',
            'isPublic' => true,
        ];

        $form->submit($formData);

        $this->assertFalse($form->isValid());
        $this->assertTrue($form->get('name')->getErrors()->count() > 0);
    }

    public function testFormDefaultValues(): void
    {
        $form = $this->factory->create(FoundsImageUploadType::class);
        $view = $form->createView();

        // Test Standardwerte
        $this->assertFalse($view->children['isPublic']->vars['checked']);
    }

    public function testFormWithEntity(): void
    {
        $entity = new FoundsImage();
        $entity->setName('Existing Image');
        $entity->note = 'Existing note';
        $entity->isPublic = true;

        $form = $this->factory->create(FoundsImageUploadType::class, $entity);
        $view = $form->createView();

        $this->assertEquals('Existing Image', $view->children['name']->vars['value']);
        $this->assertEquals('Existing note', $view->children['note']->vars['value']);
        $this->assertTrue($view->children['isPublic']->vars['checked']);
    }
} 