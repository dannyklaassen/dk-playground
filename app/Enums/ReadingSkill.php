<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum ReadingSkill: string implements HasLabel
{
    case MainIdea = 'main_idea';
    case FindingInformation = 'finding_information';
    case CauseAndEffect = 'cause_and_effect';
    case DrawingConclusions = 'drawing_conclusions';
    case ReferenceWords = 'reference_words';
    case WordMeaningFromContext = 'word_meaning_from_context';
    case SequenceOfEvents = 'sequence_of_events';
    case AuthorsPurpose = 'authors_purpose';

    public function getLabel(): string
    {
        return __("common.reading_skill.{$this->value}");
    }

    public function getDescription(): string
    {
        return __("common.reading_skill_descriptions.{$this->value}");
    }
}
