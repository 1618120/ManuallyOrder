<?php
namespace Order\PlaceManually\Model\MyCustomGrid\Source;

class Status implements \Magento\Framework\Data\OptionSourceInterface
{
    protected $emp;

    public function __construct(\Order\PlaceManually\Model\MyCustomGrid $emp)
    {
        $this->emp = $emp;
    }

    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray()
    {
        $options[] = ['label' => '', 'value' => ''];
        $availableOptions = $this->getOptionArray();
        foreach ($availableOptions as $key => $value) {
            $options[] = [
                'label' => $value,
                'value' => $key,
            ];
        }
        return $options;
    }

    public static function getOptionArray()
    {
        return [
            'Processing' => __('Processing'),
            'Pending' => __('Pending'),
            'On Hold' => __('On Hold'),
            'Cancel' => __('Cancel')
        ];
    }
}