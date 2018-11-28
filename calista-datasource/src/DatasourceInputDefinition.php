<?php

declare(strict_types=1);

namespace MakinaCorpus\Calista\Datasource;

use MakinaCorpus\Calista\Query\InputDefinition;

class DatasourceInputDefinition extends InputDefinition
{
    /**
     * Default constructor
     */
    public function __construct(DatasourceInterface $datasource, array $options = [])
    {
        $options['filter_list'] = $datasource->getFilters();
        $options['sort_allowed_list'] =  $datasource->getSorts();

        if (!$datasource->supportsFulltextSearch() && ($options['search_enable'] ?? false) && !($options['search_parse'] ?? false)) {
            throw new \InvalidArgumentException("datasource cannot do fulltext search, yet it is enabled, but search parse is disabled");
        }

        if (!$datasource->supportsPagination() && !empty($options['pager_enable'])) {
            throw new \InvalidArgumentException("datasource cannot do paging, yet it is enabled");
        }

        parent::__construct($options);
    }
}
