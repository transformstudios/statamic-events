<?php

namespace TransformStudios\Events;

use Statamic\Facades\Entry;
use Statamic\Support\Arr;
use Statamic\Tags\Collection\Entries as BaseEntries;

class Entries extends BaseEntries
{
    // we don't support these
    protected $ignoredParams = ['as', 'from', 'offset', 'order_by', 'paginate', 'limit', 'since', 'sort', 'until'];

    /*
        Same as the parent but removed the queries we don't support
    */
    protected function query()
    {
        $query = Entry::query()
            ->whereIn('collection', $this->collections->map->handle()->all())
            ->when(
                $this->params->get('event'),
                fn ($query, $id) => $query->whereIn('id', Arr::wrap($id))
            );

        $this->querySite($query);
        $this->queryPublished($query);
        $this->queryTaxonomies($query);
        $this->queryConditions($query);
        $this->queryScopes($query);

        return $query;
    }
}
