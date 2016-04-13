<?php

require_once(__DIR__."/LoopsTestCase.php");

use Loops\ArrayObject;
use Loops\Annotations\Listen;
use Loops\Annotations\Event\Form\onCleanup;
use Loops\Annotations\Event\Form\onConfirm;
use Loops\Annotations\Event\Form\onSubmit;
use Loops\Annotations\Event\Form\onValidate;
use Loops\Annotations\Form\Element;
use Loops\Annotations\Form\Validator;
use Loops\Annotations\Form\Filter;
use Loops\Annotations\Element\Form as AForm;
use Loops\Application\WebApplication;
use Loops\Form;
use Loops\Form\Element\Text;

class TestEntity extends ArrayObject {
    /**
     * @Element("Text")
     */
    public $formelement_key_1;
    
    /**
     * @Element("Number")
     */
    public $formelement_key_2;
    
    public $non_formelement_key;
}

class TestEntityValidator extends ArrayObject {
    /**
     * @Element("Text")
     * @Validator("Required")
     */
    public $single;
    
    /**
     * @Element("Text")
     * @Validator("Required")
     * @Validator("Length")
     */
    public $multiple;
}

class TestEntityFilter extends ArrayObject {
    /**
     * @Element("Text")
     * @Filter("Number")
     */
    public $single;
    
    /**
     * @Element("Text")
     * @Filter("Text")
     * @Filter("Date")
     */
    public $multiple;
}

class TestEntityEvents extends ArrayObject {
    /**
     * @onSubmit
     */
    public function submitTest() {
        echo "s1";
        return TRUE;
    }
    
    /**
     * @Listen("Form\onSubmit")
     */
    public function submitTestListen() {
        echo "s2";
        return TRUE;
    }
    
    /**
     * @onValidate
     */
    public function validateTest() {
        echo "v1";
        return TRUE;
    }
    
    /**
     * @Listen("Form\onValidate")
     */
    public function validateTestListen() {
        echo "v2";
        return TRUE;
    }
    
    /**
     * @onConfirm
     */
    public function confirmTest() {
        echo "c1";
        return TRUE;
    }
    
    /**
     * @Listen("Form\onConfirm")
     */
    public function confirmTestListen() {
        echo "c2";
        return TRUE;
    }
    
    /**
     * @onCleanup
     */
    public function cleanupTest() {
        echo "l1";
    }
    
    /**
     * @Listen("Form\onCleanup")
     */
    public function cleanupTestListen() {
        echo "l2";
    }
}

class TestForm extends Form {
    /**
     * @Element("Text")
     */
    public $formelement_key_1b;
    
    /**
     * @Element("Number")
     */
    public $formelement_key_2b;
    
    public $non_formelement_keyb;
}

class TestFormEvents extends Form {
    /**
     * @onSubmit
     */
    public function submitTest() {
        echo "fs1";
        return TRUE;
    }
    
    /**
     * @Listen("Form\onSubmit")
     */
    public function submitTestListen() {
        echo "fs2";
        return TRUE;
    }
    
    /**
     * @onConfirm
     */
    public function confirmTest() {
        echo "fc1";
        return TRUE;
    }
    
    /**
     * @Listen("Form\onConfirm")
     */
    public function confirmTestListen() {
        echo "fc2";
        return TRUE;
    }
    
    /**
     * @onCleanup
     */
    public function cleanupTest() {
        echo "fl1";
    }
    
    /**
     * @Listen("Form\onCleanup")
     */
    public function cleanupTestListen() {
        echo "fl2";
    }
}

class TestFormWithFilter extends Form {
    /**
     * @Element("Text",filter="a")
     */
    public $formelement_a;
    
    /**
     * @Element("Text",filter="b")
     */
    public $formelement_b;
    
    /**
     * @Element("Text",filter={"a","b"})
     */
    public $formelement_ab;
    
    /**
     * @Element("Text")
     */
    public $formelement_all;
}

class SimpleTestForm extends Form {
    /**
     * @Element("Number")
     */
    public $test;
}

class SimpleTestFormWithClean extends Form {
    /**
     * @Element("TextWithCleanTrigger")
     */
    public $test;
}

class SimpleTestFormManualFailure extends Form {
    /**
     * @Element("Number")
     */
    public $test;
    
    /**
     * @onConfirm
     */
    public function confirmFormEvent($value) {
        return FALSE;
    }
}

class ManuallyAddFormElementTest {
    /**
     * @Element("Number")
     */
    public $manual;
    
    /**
     * @onConfirm
     */
    public function confirmFormEvent($value) {
        echo $value["manual"];
        return TRUE;
    }
}

class SubFormElementTest extends Form {
    /**
     * @Element("SubForm",form=@AForm("SimpleTestForm"))
     */
    public $sub;
}

class LoopsFormTest extends LoopsTestCase {
    function testDefaultEntity() {
        $app = new WebApplication(__DIR__."/app", "/");
        
        $form = new Form;
        $this->assertInstanceOf("ArrayAccess", $form->value);
    }
    
    function testAddFromAnnotationDefault() {
        $app = new WebApplication(__DIR__."/app", "/");
        $form = new Form;
        $result = $form->addFromAnnotations("ManuallyAddFormElementTest");
        
        $elements = $form->getFormElements();
        
        $this->assertCount(1, $elements);
        $this->assertArrayHasKey("manual", $elements);
        $this->assertInstanceOf("Loops\Form\Element\Number", $elements["manual"]);
        $this->assertSame($result, $elements);
    }
    
    function testAddFromAnnotationEvents() {
        $this->expectOutputString("123");
        $app = new WebApplication(__DIR__."/app", "/");
        $form = new Form;
        $form->addFromAnnotations(new ManuallyAddFormElementTest);
        $this->assertTrue($form->confirm(["manual"=>123]));
    }
    
    function testEmptyProcessing() {
        $app = new WebApplication(__DIR__."/app", "/");
        
        $form = new Form;
        $this->assertEmpty($form->getFormElements());
        
        $confirmed = $form->confirm([]);
        
        $this->assertTrue($confirmed);
        $this->assertTrue($form->confirmed);
        
        $submitted = $form->submit();
        
        $this->assertTrue($confirmed);
        $this->assertTrue($form->confirmed);
        $this->assertTrue($submitted);
        $this->assertTrue($form->submitted);
    }
    
    function testEntityInit() {
        $app = new WebApplication(__DIR__."/app", "/");
        $form = new Form(new TestEntity);
        
        $elements = $form->getFormElements();
        
        $this->assertArrayHasKey("formelement_key_1", $elements);
        $this->assertArrayHasKey("formelement_key_2", $elements);
        $this->assertArrayNotHasKey("non_formelement_key", $elements);
        
        $this->assertInstanceOf("Loops\Form\Element\Text", $elements["formelement_key_1"]);
        $this->assertInstanceOf("Loops\Form\Element\Number", $elements["formelement_key_2"]);
    }
    
    function testInheritanceInit() {
        $app = new WebApplication(__DIR__."/app", "/");
        $form = new TestForm;
        
        $elements = $form->getFormElements();
        
        $this->assertArrayHasKey("formelement_key_1b", $elements);
        $this->assertArrayHasKey("formelement_key_2b", $elements);
        $this->assertArrayNotHasKey("non_formelement_keyb", $elements);
        
        $this->assertInstanceOf("Loops\Form\Element\Text", $elements["formelement_key_1b"]);
        $this->assertInstanceOf("Loops\Form\Element\Number", $elements["formelement_key_2b"]);
    }
    
    function testMixedInit() {
        $app = new WebApplication(__DIR__."/app", "/");
        $form = new TestForm(new TestEntity);
        
        $elements = $form->getFormElements();
        
        $this->assertArrayHasKey("formelement_key_1", $elements);
        $this->assertArrayHasKey("formelement_key_2", $elements);
        $this->assertArrayNotHasKey("non_formelement_key", $elements);
        $this->assertArrayHasKey("formelement_key_1b", $elements);
        $this->assertArrayHasKey("formelement_key_2b", $elements);
        $this->assertArrayNotHasKey("non_formelement_keyb", $elements);
        
        $this->assertInstanceOf("Loops\Form\Element\Text", $elements["formelement_key_1b"]);
        $this->assertInstanceOf("Loops\Form\Element\Number", $elements["formelement_key_2b"]);
        $this->assertInstanceOf("Loops\Form\Element\Text", $elements["formelement_key_1"]);
        $this->assertInstanceOf("Loops\Form\Element\Number", $elements["formelement_key_2"]);
    }
    
    function testEntityInitValidator() {
        $app = new WebApplication(__DIR__."/app", "/");
        $form = new Form(new TestEntityValidator);
        
        $elements = $form->getFormElements();
        
        $this->assertCount(1, $elements["single"]->validators);
        $this->assertInstanceOf("Loops\Form\Element\Validator\Required", $elements["single"]->validators[0]);
        
        $this->assertCount(2, $elements["multiple"]->validators);
    }
    
    function testEntityInitFilter() {
        $app = new WebApplication(__DIR__."/app", "/");
        $form = new Form(new TestEntityFilter);
        
        $elements = $form->getFormElements();
        
        $this->assertCount(1, $elements["single"]->filters);
        $this->assertInstanceOf("Loops\Form\Element\Filter\Number", $elements["single"]->filters[0]);
        
        $this->assertCount(2, $elements["multiple"]->filters);
    }
    
    function testFormElementsQuickAccess() {
        $app = new WebApplication(__DIR__."/app", "/");
        $form = new Form(new TestEntity);
        
        $this->assertSame($form->elements, $form->getFormElements());
    }
    
    function testFormEvents() {
        $app = new WebApplication(__DIR__."/app", "/");
        $form = new TestFormEvents;
        
        $this->expectOutputString("fc1fc2fs1fs2fl1fl2");
        
        $this->assertTrue($form->confirm([]));
        $this->assertTrue($form->submit());
    }
    
    function testEntityEvents() {
        $app = new WebApplication(__DIR__."/app", "/");
        $form = new Form(new TestEntityEvents);
        
        $this->expectOutputString("v1v2c1c2s1s2l1l2");
        
        $this->assertTrue($form->confirm([]));
        $this->assertTrue($form->submit());
    }
    
    function testFormEntityEventsMixed() {
        $app = new WebApplication(__DIR__."/app", "/");
        $form = new TestFormEvents(new TestEntityEvents);
        
        $this->expectOutputString("v1v2c1c2fc1fc2s1s2fs1fs2l1l2fl1fl2");
        
        $this->assertTrue($form->confirm([]));
        $this->assertTrue($form->submit());
    }
    
    function testFilter() {
        $app = new WebApplication(__DIR__."/app", "/");
        $form = new TestFormWithFilter;
        $elements = $form->getFormElements();
        $this->assertCount(1, $elements);
        $this->assertArrayHasKey("formelement_all", $elements);
    }
    
    function testFilterA() {
        $app = new WebApplication(__DIR__."/app", "/");
        
        $form = new TestFormWithFilter(NULL, "a");
        $elements = $form->getFormElements();
        $this->assertCount(2, $elements);
        $this->assertArrayHasKey("formelement_a", $elements);
        $this->assertArrayHasKey("formelement_ab", $elements);
    }
    
    function testFilterB() {
        $app = new WebApplication(__DIR__."/app", "/");
        
        $form = new TestFormWithFilter(NULL, "b");
        $elements = $form->getFormElements();
        $this->assertCount(2, $elements);
        $this->assertArrayHasKey("formelement_b", $elements);
        $this->assertArrayHasKey("formelement_ab", $elements);
    }
    
    function testFilterNone() {
        $app = new WebApplication(__DIR__."/app", "/");
        
        $form = new TestFormWithFilter(NULL, "none");
        $elements = $form->getFormElements();
        $this->assertCount(0, $elements);
    }
    
    function testFilterAB() {
        $app = new WebApplication(__DIR__."/app", "/");
        
        $form = new TestFormWithFilter(NULL, ["a","b"]);
        $elements = $form->getFormElements();
        $this->assertCount(3, $elements);
        $this->assertArrayHasKey("formelement_a", $elements);
        $this->assertArrayHasKey("formelement_b", $elements);
        $this->assertArrayHasKey("formelement_ab", $elements);
    }
    
    function testFilterAWithNone() {
        $app = new WebApplication(__DIR__."/app", "/");
        
        $form = new TestFormWithFilter(NULL, ["a","none"]);
        $elements = $form->getFormElements();
        $this->assertCount(2, $elements);
        $this->assertArrayHasKey("formelement_a", $elements);
        $this->assertArrayHasKey("formelement_ab", $elements);
    }
    
    function testFilterAAndDefault() {
        $app = new WebApplication(__DIR__."/app", "/");
        
        $form = new TestFormWithFilter(NULL, ["","a"]);
        $elements = $form->getFormElements();
        $this->assertCount(3, $elements);
        $this->assertArrayHasKey("formelement_a", $elements);
        $this->assertArrayHasKey("formelement_ab", $elements);
        $this->assertArrayHasKey("formelement_all", $elements);
    }
    
    function testValidate() {
        $app = new WebApplication(__DIR__."/app", "/");
        
        $form = new SimpleTestForm;
        
        $this->assertFalse($form->validate());
        $this->assertTrue($form->confirm(["test"=>"123"]));
        $this->assertTrue($form->validate());
        $this->assertFalse($form->confirm(["test"=>"abc123"]));
        $this->assertFalse($form->validate());
    }
    
    function testConfirm() {
        $app = new WebApplication(__DIR__."/app", "/");
        
        $form = new SimpleTestForm;
        
        $this->assertFalse($form->confirm(["test"=>"abc123"]));
        $this->assertTrue($form->confirm(["test"=>"123"]));
        $this->assertTrue($form->confirm(["test"=>123]));
    }
    
    function testConfirmDefaultToNULL() {
        $app = new WebApplication(__DIR__."/app", "/");
        
        $form = new SimpleTestForm;
        
        $this->assertFalse($form->confirm(["test"=>"abc123"]));
        $this->assertEquals("abc123", $form->value["test"]);
        $this->assertFalse($form->confirm([]));
        $this->assertEquals(NULL, $form->value["test"]);
    }
    
    function testConfirmManualFailure() {
        $app = new WebApplication(__DIR__."/app", "/");
        
        $form = new SimpleTestFormManualFailure;
        
        $this->assertFalse($form->confirm(["test"=>123]));
    }
    
    function testConfirmFlatInput() {
        $app = new WebApplication(__DIR__."/app", "/");
        
        $form = new SimpleTestForm;
        
        //note: text soft filter joins string
        $this->assertTrue($form->confirm(["test-0"=>1,"test-1"=>2,"test-2"=>3]));
        $this->assertSame(123, $form->value["test"]);
    }
    
    function testCleanAfterSuccessfulConfirm() {
        $app = new WebApplication(__DIR__."/app", "/");
        
        $form = new SimpleTestForm;

        $form->confirm(["test"=>"123"]);
        $this->assertSame(123, $form->value["test"]);
    }
    
    function testApplyFilter() {
        $app = new WebApplication(__DIR__."/app", "/");
        
        $form = new SimpleTestForm(new ArrayObject(["test"=>"123"]));
        
        $this->assertSame($form->value["test"], "123");
        
        $form->applyFilter();
        
        $this->assertSame($form->value["test"], 123);
    }
    
    function testInvalidSubmit() {
        $this->setExpectedException("Loops\Exception");
        
        $app = new WebApplication(__DIR__."/app", "/");
        
        $form = new Form;
        $form->submit();
    }
    
    function testBack() {
        $app = new WebApplication(__DIR__."/app", "/");
        
        $form = new Form;
        $form->confirm([]);
        $this->assertTrue($form->confirmed);
        $form->back();
        $this->assertFalse($form->confirmed);
    }
    
    function testValue() {
        $app = new WebApplication(__DIR__."/app", "/");
        
        $value = new ArrayObject(["test"=>"123abc"]);
        
        $form = new SimpleTestForm($value);
        
        $this->assertSame($value, $form->value);
        
        $form->confirm(["test"=>123]);
        
        $this->assertSame($value, $form->value);
        $this->assertSame(123, $form->value["test"]);
    }
    
    function testValueAsArray() {
        $app = new WebApplication(__DIR__."/app", "/");
        
        $value = new ArrayObject(["test"=>"123abc"]);
        
        $form = new SimpleTestForm($value);
        $this->assertSame(["test"=>"123abc"], $form->getValue(TRUE, FALSE));
        $this->assertSame(["test"=>123], $form->getValue(TRUE));
    }
    
    private function obRun($app) {
        ob_start();
        $app->run();
        ob_get_clean();
    }
    
    function testOnPage() {
        Loops\Session\TestSession::reset();
        
        $app = new WebApplication(__DIR__."/app", "/testform", "GET", [], []);
        $this->obRun($app);
    
        $this->assertFalse($app->web_core->page->form->confirmed);
        
        $app = new WebApplication(__DIR__."/app", "/testform/form/confirm", "POST", [], [ "test" => "123" ]);
        $this->obRun($app);
        
        $this->assertTrue($app->web_core->page->form->confirmed);
        
        $app = new WebApplication(__DIR__."/app", "/testform/form/submit", "POST", [], []);
        $this->obRun($app);
        
        $this->assertTrue($app->web_core->page->form->confirmed);
        $this->assertTrue($app->web_core->page->form->submitted);
    }
    
    function testOnPageSession() {
        Loops\Session\TestSession::reset();
        
        $app = new WebApplication(__DIR__."/app", "/testform/form/confirm", "POST", [], [ "test" => "123" ]);
        $this->obRun($app);
        
        $this->assertTrue($app->web_core->page->form->confirmed);
        
        $app = new WebApplication(__DIR__."/app", "/testform", "GET", [], []);
        $this->obRun($app);
        
        $app->web_core->page->form->initFromSession();
        
        $this->assertEquals(123, $app->web_core->page->form->value["test"]);
    }
    
    function testOnPageBack() {
        Loops\Session\TestSession::reset();
        
        $app = new WebApplication(__DIR__."/app", "/testform/form/confirm", "POST", [], [ "test" => "123" ]);
        $this->obRun($app);
        
        $this->assertTrue($app->web_core->page->form->confirmed);
        
        $app = new WebApplication(__DIR__."/app", "/testform/form/confirm", "GET", [], []);
        $this->obRun($app);
        
        $this->assertTrue($app->web_core->page->form->confirmed);
        
        $app = new WebApplication(__DIR__."/app", "/testform/form/back", "GET", [], []);
        $this->obRun($app);
        
        $this->assertSame(302, $app->response->status_code);
        $this->assertContains("Location: /testform/form", $app->response->extra_header);
        
        $app = new WebApplication(__DIR__."/app", "/testform/form", "GET", [], []);
        $this->obRun($app);
        
        $app->web_core->page->form->initFromSession();
        
        $this->assertFalse($app->web_core->page->form->confirmed);
    }
    
    function testOnPageBackExtraParams() {
        Loops\Session\TestSession::reset();
        
        $app = new WebApplication(__DIR__."/app", "/testform/form/confirm", "POST", [], [ "test" => "123" ]);
        $this->obRun($app);
        
        $this->assertTrue($app->web_core->page->form->confirmed);
        
        $app = new WebApplication(__DIR__."/app", "/testform/form/back/extra", "GET", [], []);
        $this->obRun($app);
        
        $this->assertSame(404, $app->response->status_code);
    }
    
    function testOnPageConfirmExtraParams() {
        Loops\Session\TestSession::reset();
        
        $app = new WebApplication(__DIR__."/app", "/testform/form2/confirm/extra", "POST", [], [ "test" => "123" ]);
        $this->obRun($app);
        
        $this->assertSame(404, $app->response->status_code);
    }
    
    function testOnPageConfirmRedirectOnGet() {
        Loops\Session\TestSession::reset();
        
        $app = new WebApplication(__DIR__."/app", "/testform/form/confirm", "GET", [], []);
        $this->obRun($app);
        
        $this->assertSame(302, $app->response->status_code);
        $this->assertContains("Location: /testform/form", $app->response->extra_header);
    }
    
    function testOnPageSubmitRedirectOnGet() {
        Loops\Session\TestSession::reset();
        
        $app = new WebApplication(__DIR__."/app", "/testform/form/submit", "GET", [], []);
        $this->obRun($app);
        
        $this->assertSame(302, $app->response->status_code);
        $this->assertContains("Location: /testform/form", $app->response->extra_header);
        
        $app = new WebApplication(__DIR__."/app", "/testform/form", "GET", [], []);
        $this->obRun($app);
        
        $app->web_core->page->form->initFromSession();
        
        $this->assertFalse($app->web_core->page->form->confirmed);
    }
    
    function testOnPageSubmitRedirectOnNotConfirmed() {
        Loops\Session\TestSession::reset();
        
        $app = new WebApplication(__DIR__."/app", "/testform/form/submit", "POST", [], []);
        $this->obRun($app);
        
        $this->assertSame(302, $app->response->status_code);
        $this->assertContains("Location: /testform/form", $app->response->extra_header);
    }
    
    function testOnPageSubmitNoConfirmOption() {
        Loops\Session\TestSession::reset();
        
        $app = new WebApplication(__DIR__."/app", "/testform/form2/submit", "POST", [], [ "test" => "123" ]);
        $this->obRun($app);
        
        $this->assertTrue($app->web_core->page->form2->confirmed);
        $this->assertTrue($app->web_core->page->form2->submitted);
    }
    
    function testOnPageSubmitNoConfirmOptionValidationFailed() {
        Loops\Session\TestSession::reset();
        
        $app = new WebApplication(__DIR__."/app", "/testform/form2/submit", "POST", [], [ "test" => "abc123" ]);
        $this->obRun($app);
        
        $this->assertFalse($app->web_core->page->form2->confirmed);
    }
    
    function testOnPageSubmitSessionCleared() {
        Loops\Session\TestSession::reset();
        
        $app = new WebApplication(__DIR__."/app", "/testform", "GET", [], []);
        $this->obRun($app);
    
        $this->assertFalse($app->web_core->page->form->confirmed);
        
        $app = new WebApplication(__DIR__."/app", "/testform/form/confirm", "POST", [], [ "test" => "123" ]);
        $this->obRun($app);
        
        $this->assertTrue($app->web_core->page->form->confirmed);
        
        $app = new WebApplication(__DIR__."/app", "/testform/form/submit", "POST", [], []);
        $this->obRun($app);
        
        $this->assertTrue($app->web_core->page->form->confirmed);
        $this->assertTrue($app->web_core->page->form->submitted);
        
        $app = new WebApplication(__DIR__."/app", "/testform", "GET", [], []);
        $this->obRun($app);
        
        $app->web_core->page->form->initFromSession();
        
        $this->assertFalse($app->web_core->page->form->confirmed);
    }
    
    function testChildCleanup() {
        $this->expectOutputString("Loops\Form\Element\TextWithCleanTriggerSimpleTestFormWithClean");
        
        $app = new WebApplication(__DIR__."/app", "/");
        
        $form = new SimpleTestFormWithClean;
        $this->assertTrue($form->confirm(["test"=>"test"]));
        $this->assertTrue($form->submit());
    }
    
    function testFormValue() {
        $app = new WebApplication(__DIR__."/app", "/");
        
        $value = new ArrayObject(["test"=>"123abc"]);
        
        $form = new SimpleTestForm($value);
        
        $formvalue = $form->getFormValue();
        
        $this->assertInstanceOf("Loops\Form\Value", $formvalue);
        
        $this->assertSame($value->toArray(), $formvalue->toArray());
        $this->assertSame($form, $formvalue->getForm());
    }
    
    function testFormValueStrict() {
        $app = new WebApplication(__DIR__."/app", "/");
        
        $value = new ArrayObject(["test"=>"123abc"]);
        
        $form = new SimpleTestForm($value);
        
        $formvalue = $form->getFormValue(TRUE);
        
        $this->assertInstanceOf("Loops\Form\Value", $formvalue);
        
        $this->assertSame(["test"=>123], $formvalue->toArray());
    }
    
    function testFormValueSubform() {
        $app = new WebApplication(__DIR__."/app", "/");
        
        $form = new SubFormElementTest;
        
        $value = $form->getValue();
        $formvalue = $form->getFormValue(TRUE);
        
        $this->assertInstanceOf("Loops\Form\Value", $formvalue);
        $this->assertInstanceOf("Loops\Form\Value", $formvalue->offsetGet("sub"));
        $this->assertNotSame($value["sub"], $formvalue->offsetGet("sub"));
    }
}