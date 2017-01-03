<?php
namespace DrdPlus\Lighting;

use DrdPlus\Calculations\SumAndRound;
use DrdPlus\Codes\RaceCode;
use DrdPlus\Lighting\Partials\LightingQualityInterface;
use DrdPlus\Tables\Races\SightRangesTable;
use Granam\Integer\PositiveInteger;
use Granam\Strict\Object\StrictObject;

/**
 * See PPH page 130 right column, @link https://pph.drdplus.jaroslavtyc.com/#rozsirujici_pravidla_pro_videni
 */
class EyesAdaptation extends StrictObject implements LightingQualityInterface
{
    /**
     * @var int
     */
    private $value;

    /**
     * @param LightingQuality $previousLightingQuality
     * @param LightingQuality $currentLightingQuality
     * @param RaceCode $raceCode
     * @param SightRangesTable $sightRangesTable
     * @param PositiveInteger $roundsOfAdaptation
     */
    public function __construct(
        LightingQuality $previousLightingQuality,
        LightingQuality $currentLightingQuality,
        RaceCode $raceCode,
        SightRangesTable $sightRangesTable,
        PositiveInteger $roundsOfAdaptation
    )
    {
        $this->value = $this->calculateValue(
            $previousLightingQuality,
            $currentLightingQuality,
            $raceCode,
            $sightRangesTable,
            $roundsOfAdaptation
        );
    }

    /**
     * @param LightingQuality $previousLightingQuality
     * @param LightingQuality $currentLightingQuality
     * @param RaceCode $raceCode
     * @param SightRangesTable $sightRangesTable
     * @param PositiveInteger $roundsOfAdaptation how much time did you have to get used to current lighting
     * @return int
     */
    private function calculateValue(
        LightingQuality $previousLightingQuality,
        LightingQuality $currentLightingQuality,
        RaceCode $raceCode,
        SightRangesTable $sightRangesTable,
        PositiveInteger $roundsOfAdaptation
    )
    {
        $maximalLighting = $sightRangesTable->getMaximalLighting($raceCode);
        $minimalLighting = $sightRangesTable->getMinimalLighting($raceCode);
        $previousLighting = $previousLightingQuality->getValue();
        if ($previousLighting < $minimalLighting) {
            $previousLighting = $minimalLighting;
        } else if ($previousLighting > $maximalLighting) {
            $previousLighting = $maximalLighting;
        }
        $currentLighting = $currentLightingQuality->getValue();
        if ($currentLighting < $minimalLighting) {
            $currentLighting = $minimalLighting;
        } elseif ($currentLighting > $maximalLighting) {
            $currentLighting = $maximalLighting;
        }
        if ($previousLighting === $currentLighting) {
            return $currentLighting; // nothing to adapt at all
        }

        $difference = $previousLighting - $currentLighting;
        $needsAdaptForRounds = abs($difference); // needs one round if came to a darker place
        if ($difference < 0) { // from dark to light
            $needsAdaptForRounds *= 10; // needs ten rounds if came to lighter place
        }
        $effectiveRoundsOfAdaptation = $needsAdaptForRounds;
        if ($roundsOfAdaptation->getValue() < $needsAdaptForRounds) {
            $effectiveRoundsOfAdaptation = $roundsOfAdaptation->getValue();
        }

        if ($difference > 0) { // from light to dark
            return -$effectiveRoundsOfAdaptation; // needs one round for one point of difference if came to a darker place
        }

        return SumAndRound::floor($effectiveRoundsOfAdaptation / 10); // needs ten rounds for one point of difference if came to lighter place
    }

    /**
     * @return int
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return (string)$this->getValue();
    }

}