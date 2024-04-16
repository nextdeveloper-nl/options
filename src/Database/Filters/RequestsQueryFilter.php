<?php

namespace NextDeveloper\Options\Database\Filters;

use Illuminate\Database\Eloquent\Builder;
use NextDeveloper\Commons\Database\Filters\AbstractQueryFilter;


/**
 * This class automatically puts where clause on database so that use can filter
 * data returned from the query.
 */
class RequestsQueryFilter extends AbstractQueryFilter
{
    /**
     * @var Builder
     */
    protected $builder;
    
    public function uri($value)
    {
        return $this->builder->where('uri', 'like', '%' . $value . '%');
    }
    
    public function method($value)
    {
        return $this->builder->where('method', 'like', '%' . $value . '%');
    }
    
    public function controller($value)
    {
        return $this->builder->where('controller', 'like', '%' . $value . '%');
    }
    
    public function topic($value)
    {
        return $this->builder->where('topic', 'like', '%' . $value . '%');
    }
    
    public function controllerDescription($value)
    {
        return $this->builder->where('controller_description', 'like', '%' . $value . '%');
    }
    
    public function actionDescription($value)
    {
        return $this->builder->where('action_description', 'like', '%' . $value . '%');
    }

    public function createdAtStart($date) 
    {
        return $this->builder->where('created_at', '>=', $date);
    }

    public function createdAtEnd($date) 
    {
        return $this->builder->where('created_at', '<=', $date);
    }

    public function updatedAtStart($date) 
    {
        return $this->builder->where('updated_at', '>=', $date);
    }

    public function updatedAtEnd($date) 
    {
        return $this->builder->where('updated_at', '<=', $date);
    }

    public function deletedAtStart($date) 
    {
        return $this->builder->where('deleted_at', '>=', $date);
    }

    public function deletedAtEnd($date) 
    {
        return $this->builder->where('deleted_at', '<=', $date);
    }

    // EDIT AFTER HERE - WARNING: ABOVE THIS LINE MAY BE REGENERATED AND YOU MAY LOSE CODE\n\n\n\n\n\n\n\n\n\n\n\n
}