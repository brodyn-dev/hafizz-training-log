<?php

declare(strict_types=1);

namespace App\Controller\File\Dashboard;

use App\Domain\Dashboard\DashboardOpenGraphImage;
use App\Infrastructure\Http\Response\CachePolicy;
use App\Infrastructure\Http\Response\PngResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
final readonly class DashboardOgImageRequestHandler
{
    public function __construct(
        private DashboardOpenGraphImage $dashboardOpenGraphImage,
    ) {
    }

    #[Route(path: '/dashboard/og-image.png', name: 'dashboard_og_image', methods: ['GET'])]
    public function handle(): PngResponse
    {
        return CachePolicy::public()->maxAge(3600)->applyTo(
            new PngResponse($this->dashboardOpenGraphImage->render()),
        );
    }
}
