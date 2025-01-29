<?php

namespace TransformStudios\Events\Dictionaries;

use Statamic\Dictionaries\BasicDictionary;

class MonthDayRecurrence extends BasicDictionary
{
    protected function getItems(): array
    {
        return [
            ['label' => 'First Sunday', 'value' => 'first_sunday', 'rrule' => '1SU'],
            ['label' => 'First Monday', 'value' => 'first_monday', 'rrule' => '1MO'],
            ['label' => 'First Tuesday', 'value' => 'first_tuesday', 'rrule' => '1TU'],
            ['label' => 'First Wednesday', 'value' => 'first_wednesday', 'rrule' => '1WE'],
            ['label' => 'First Thursday', 'value' => 'first_thursday', 'rrule' => '1TH'],
            ['label' => 'First Friday', 'value' => 'first_friday', 'rrule' => '1FR'],
            ['label' => 'First Saturday', 'value' => 'first_saturday', 'rrule' => '1SA'],
            ['label' => 'Second Sunday', 'value' => 'second_sunday', 'rrule' => '2SU'],
            ['label' => 'Second Monday', 'value' => 'second_monday', 'rrule' => '2MO'],
            ['label' => 'Second Tuesday', 'value' => 'second_tuesday', 'rrule' => '2TU'],
            ['label' => 'Second Wednesday', 'value' => 'second_wednesday', 'rrule' => '2WE'],
            ['label' => 'Second Thursday', 'value' => 'second_thursday', 'rrule' => '2TH'],
            ['label' => 'Second Friday', 'value' => 'second_friday', 'rrule' => '2FR'],
            ['label' => 'Second Saturday', 'value' => 'second_saturday', 'rrule' => '2SA'],
            ['label' => 'Third Sunday', 'value' => 'third_sunday', 'rrule' => '3SU'],
            ['label' => 'Third Monday', 'value' => 'third_monday', 'rrule' => '3MO'],
            ['label' => 'Third Tuesday', 'value' => 'third_tuesday', 'rrule' => '3TU'],
            ['label' => 'Third Wednesday', 'value' => 'third_wednesday', 'rrule' => '3WE'],
            ['label' => 'Third Thursday', 'value' => 'third_thursday', 'rrule' => '3TH'],
            ['label' => 'Third Friday', 'value' => 'third_friday', 'rrule' => '3FR'],
            ['label' => 'Third Saturday', 'value' => 'third_saturday', 'rrule' => '3SA'],
            ['label' => 'Fourth Sunday', 'value' => 'fourth_sunday', 'rrule' => '4SU'],
            ['label' => 'Fourth Monday', 'value' => 'fourth_monday', 'rrule' => '4MO'],
            ['label' => 'Fourth Tuesday', 'value' => 'fourth_tuesday', 'rrule' => '4TU'],
            ['label' => 'Fourth Wednesday', 'value' => 'fourth_wednesday', 'rrule' => '4WE'],
            ['label' => 'Fourth Thursday', 'value' => 'fourth_thursday', 'rrule' => '4TH'],
            ['label' => 'Fourth Friday', 'value' => 'fourth_friday', 'rrule' => '4FR'],
            ['label' => 'Fourth Saturday', 'value' => 'fourth_saturday', 'rrule' => '4SA'],
            ['label' => 'Fifth Sunday', 'value' => 'fifth_sunday', 'rrule' => '5SU'],
            ['label' => 'Fifth Monday', 'value' => 'fifth_monday', 'rrule' => '5MO'],
            ['label' => 'Fifth Tuesday', 'value' => 'fifth_tuesday', 'rrule' => '5TU'],
            ['label' => 'Fifth Wednesday', 'value' => 'fifth_wednesday', 'rrule' => '5WE'],
            ['label' => 'Fifth Thursday', 'value' => 'fifth_thursday', 'rrule' => '5TH'],
            ['label' => 'Fifth Friday', 'value' => 'fifth_friday', 'rrule' => '5FR'],
            ['label' => 'Fifth Saturday', 'value' => 'fifth_saturday', 'rrule' => '5SA'],
            ['label' => 'Last Sunday', 'value' => 'last_sunday', 'rrule' => '-1 SU'],
            ['label' => 'Last Monday', 'value' => 'last_monday', 'rrule' => '-1 MO'],
            ['label' => 'Last Tuesday', 'value' => 'last_tuesday', 'rrule' => '-1 TU'],
            ['label' => 'Last Wednesday', 'value' => 'last_wednesday', 'rrule' => '-1 WE'],
            ['label' => 'Last Thursday', 'value' => 'last_thursday', 'rrule' => '-1 TH'],
            ['label' => 'Last Friday', 'value' => 'last_friday', 'rrule' => '-1 FR'],
            ['label' => 'Last Saturday', 'value' => 'last_saturday', 'rrule' => '-1 SA'],
        ];
    }
}
