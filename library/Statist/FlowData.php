<?php


namespace Statist;

/**
 * Class FlowData
 * 
 * Протоптип контейнера столбца данных статистики 
 * 
 * @package Statist
 */
class FlowData {
    public $order;
    
    public $field = '';
    public $fieldId = 0;
    public $group_id = 0;
    
    public $data = [];
    public $dataRelated = [];
    public $dataUnq = [];
    public $title = 'data flow';
    
    public $subTitle = 'gr Title';
    
    public $hideOnGraph = false;
    
    /**
     * @var FlowData;
     */
    public $rightFlow;
    
    public $flowPack;
    
    protected $booted = false;
    
    protected static $acceptedFields = [];
    protected static $acceptedFieldsIdx = [];
    protected static $acceptedDataBindIdx = [];
    
    
    private function loadAcceptedFields () {
        self::$acceptedFieldsIdx = array_flip(array_keys(get_object_vars(new FlowData())));
    
        $skip = [
            'booted',
            'rightFlow',
            'hideOnGraph',
            'title',
            'objId'
        ];
        
        self::$acceptedDataBindIdx = self::$acceptedFieldsIdx;
        
        foreach ($skip as $skipProp) {
            unset(self::$acceptedFieldsIdx[$skipProp]);
        }
        
        self::$acceptedFields = array_keys(self::$acceptedFieldsIdx);
    }
    
    public function getTitle () 
    {
        return $this->title;
    }
    
    public function bindIn (FlowData $bind, $skipFields = []) 
    {
        !self::$acceptedFields && $this->loadAcceptedFields();
        
        $skipFieldsIdx = $skipFields ? array_flip($skipFields) : null;
        
        foreach (self::$acceptedFields as $key) {
            if ($skipFieldsIdx && isset($skipFieldsIdx[$key])) {
                continue;
            }
    
            $this->$key = $bind->$key;
        }
        
        return $this;
    }
    
    public function copyFields (FlowData $bind, $fields = [])
    {
        !self::$acceptedFields && $this->loadAcceptedFields();
        
        foreach ($fields as $key) {
            if (!isset(self::$acceptedFieldsIdx[$key])) {
                continue;
            }
            
            $this->$key = $bind->$key;
        }
        
        return $this;
    }
    
    public function bindInData ($data)
    {
        !self::$acceptedFields && $this->loadAcceptedFields();
        
        foreach ($data as $key => $value) {
            if (!isset(self::$acceptedDataBindIdx[$key])) {
                continue;
            }
            
            if ($key === 'subTitle') {
                $this->$key = $value.' - '.$this->$key;
            } else {
                $this->$key = $value;       
            }
        }
        
        return $this;
    }
    
    
    public function boot() 
    {
        return $this;
    }
}