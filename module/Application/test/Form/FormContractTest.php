<?php

declare(strict_types=1);

namespace ApplicationTest\Form;

use Application\Form\AppForm;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;

class FormContractTest extends TestCase
{
    public function test_moi_form_module_co_form_name_rieng_va_csrf_dung_luong(): void
    {
        $formNames = [];
        foreach ($this->formClasses() as $class) {
            $reflection = new ReflectionClass($class);
            $formName = $reflection->getReflectionConstant('FORM_NAME')?->getValue();
            $requiresCsrf = $reflection->getReflectionConstant('REQUIRE_CSRF')?->getValue();

            self::assertIsString($formName, "{$class} chưa khai FORM_NAME.");
            self::assertNotSame('', trim($formName), "{$class} có FORM_NAME rỗng.");
            self::assertArrayNotHasKey($formName, $formNames, "FORM_NAME bị trùng: {$formName}");

            if (str_ends_with($class, 'SearchForm')) {
                self::assertFalse($requiresCsrf, "{$class} là form GET/search nên phải tắt CSRF.");
            } else {
                self::assertTrue($requiresCsrf, "{$class} là form thao tác nên phải bật CSRF.");
            }

            $formNames[$formName] = $class;
        }

        self::assertGreaterThan(0, count($formNames));
    }

    /** @return list<class-string<AppForm>> */
    private function formClasses(): array
    {
        $root = dirname(__DIR__, 4) . '/module';
        $classes = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));
        foreach ($iterator as $file) {
            if (!$file->isFile() || $file->getExtension() !== 'php' || !str_contains($file->getPathname(), DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Form' . DIRECTORY_SEPARATOR)) {
                continue;
            }

            $relative = str_replace([$root . DIRECTORY_SEPARATOR, '.php'], '', $file->getPathname());
            $parts = explode(DIRECTORY_SEPARATOR, $relative);
            if (($parts[1] ?? null) !== 'src') {
                continue;
            }
            $class = $parts[0] . '\\' . implode('\\', array_slice($parts, 2));
            if (class_exists($class) && is_subclass_of($class, AppForm::class)) {
                $classes[] = $class;
            }
        }
        sort($classes);

        return $classes;
    }
}
