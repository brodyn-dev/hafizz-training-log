<?php

declare(strict_types=1);

namespace App\Domain\Dashboard;

use App\Application\PageMetadata\OpenGraphImageCanvas;
use App\Domain\Activity\FindActivityTotals\FindActivityTotals;
use App\Domain\Settings\SettingsRepository;
use App\Infrastructure\CQRS\Query\Bus\QueryBus;
use App\Infrastructure\Measurement\ProvideMeasurementFormats;
use App\Infrastructure\Measurement\Time\Hour;
use App\Infrastructure\ValueObject\String\KernelProjectDir;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class DashboardOpenGraphImage
{
    use ProvideMeasurementFormats;

    public function __construct(
        private SettingsRepository $settingsRepository,
        private TranslatorInterface $translator,
        private QueryBus $queryBus,
        private Client $client,
        private KernelProjectDir $kernelProjectDir,
    ) {
    }

    public function render(): string
    {
        $general = $this->settingsRepository->general();
        $appearance = $this->settingsRepository->appearance();
        $unitSystem = $appearance->getUnitSystem();
        $activityTotals = $this->queryBus->ask(new FindActivityTotals());

        $profilePicture = null;
        if ('' !== ($profilePictureUrl = (string) $general->getProfilePictureUrl())) {
            try {
                $profilePicture = (string) $this->client->get($profilePictureUrl, ['timeout' => 3])->getBody();
            } catch (GuzzleException) {
            }
        }

        $canvas = new OpenGraphImageCanvas($this->kernelProjectDir);
        $canvas->drawHeader(
            athleteName: (string) $general->getAthlete()->getName(),
            profilePicture: $profilePicture
        );
        $canvas->drawText(
            text: $this->translator->trans('Training overview'),
            size: 24,
            y: 262,
            weight: 'Regular',
            color: OpenGraphImageCanvas::MUTED,
        );
        $canvas->drawText(
            text: (string) ($appearance->getAppSubTitle() ?? $general->getAthlete()->getName()),
            size: 48,
            y: 336,
            weight: 'Bold',
            color: OpenGraphImageCanvas::DARK,
            maxLines: 2,
            lineHeight: 70,
        );
        $canvas->drawStats(
            stats: [
                $this->translator->trans('Activities') => $this->formatNumber($activityTotals->getTotalActivities(), 0),
                $this->translator->trans('Distance') => $this->formatUnitWithSymbol($activityTotals->getTotalDistance()->toUnitSystem($unitSystem), 0),
                $this->translator->trans('Moving time') => $this->formatUnitWithSymbol(Hour::from($activityTotals->getMovingTimeInHours()), 0),
            ],
            labelY: 486,
            valueY: 550,
            valueSize: 40,
        );

        return $canvas->toPng();
    }
}
