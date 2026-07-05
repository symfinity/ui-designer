<?php

declare(strict_types=1);

namespace Symfinity\UiDesigner\Tests\Unit\Collaboration;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfinity\UiDesigner\Collaboration\UndoOperation;
use Symfinity\UiDesigner\Collaboration\UndoStack;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

final class UndoStackTest extends TestCase
{
    #[Test]
    public function pushPopAndCap(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $session->start();
        $request = Request::create('/');
        $request->setSession($session);
        $requestStack = new RequestStack();
        $requestStack->push($request);
        $stack = new UndoStack($requestStack, maxDepth: 2);
        $stack->push(new UndoOperation('primary', 'single', 'blue.500', 'red.500', time()));
        $stack->push(new UndoOperation('danger', 'single', 'red.500', 'orange.500', time()));
        $stack->push(new UndoOperation('text', 'single', 'slate.900', 'slate.800', time()));

        self::assertTrue($stack->canUndo());
        $latest = $stack->pop();
        self::assertNotNull($latest);
        self::assertSame('text', $latest->role);
        $middle = $stack->pop();
        self::assertNotNull($middle);
        self::assertSame('danger', $middle->role);
        self::assertFalse($stack->canUndo());
    }
}
