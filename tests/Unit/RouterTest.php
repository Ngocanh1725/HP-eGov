<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Request;
use App\Core\Response;
use App\Core\Router;
use TestRunner;

class RouterTest
{
    public function setUp(): void
    {
        Router::clear();
    }

    public function testBasicGetRoute(): void
    {
        Router::get('/test-route', function () {
            return Response::html('Hello HP-eGov');
        });

        $req = new Request([], [], ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/test-route']);
        $res = Router::dispatch($req);

        TestRunner::assertEquals(200, $res->getStatusCode());
        TestRunner::assertEquals('Hello HP-eGov', $res->getContent());
    }

    public function testMethodSpoofingPostToPut(): void
    {
        Router::put('/ho-so/cap-nhat', function () {
            return Response::json(['updated' => true]);
        });

        $req = new Request(
            [],
            ['_method' => 'PUT'],
            ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/ho-so/cap-nhat']
        );
        $res = Router::dispatch($req);

        TestRunner::assertEquals(200, $res->getStatusCode());
        TestRunner::assertContains('"updated":true', $res->getContent());
    }

    public function testDynamicRegexParameter(): void
    {
        Router::get('/ho-so/{id:\d+}', function (Request $req, string $id) {
            return Response::html("Dossier ID: {$id}");
        });

        $req = new Request([], [], ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/ho-so/12345']);
        $res = Router::dispatch($req);

        TestRunner::assertEquals(200, $res->getStatusCode());
        TestRunner::assertEquals('Dossier ID: 12345', $res->getContent());
    }

    public function testRouteGroupsWithPrefix(): void
    {
        Router::group(['prefix' => '/admin/mot-cua'], function () {
            Router::get('/tiep-nhan', function () {
                return Response::html('Ban Tiếp Nhận');
            });
        });

        $req = new Request([], [], ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/admin/mot-cua/tiep-nhan']);
        $res = Router::dispatch($req);

        TestRunner::assertEquals(200, $res->getStatusCode());
        TestRunner::assertEquals('Ban Tiếp Nhận', $res->getContent());
    }

    public function testNamedRouteAndUrlGenerator(): void
    {
        Router::get('/tra-cuu/{ma_ho_so}', function () {})->name('portal.tra_cuu');

        $generated = route('portal.tra_cuu', ['ma_ho_so' => '000-260115-0042']);
        TestRunner::assertContains('/tra-cuu/000-260115-0042', $generated);
    }

    public function tearDown(): void
    {
        Router::clear();
        require dirname(__DIR__, 2) . '/routes/web.php';
        require dirname(__DIR__, 2) . '/routes/api.php';
    }
}
