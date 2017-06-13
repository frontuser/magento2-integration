<?php

namespace Frontuser\Integration\Block\Adminhtml;


class Customermap extends \Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray
{
	/**
	 * @var \Magento\Framework\Data\Form\Element\Factory
	 */
	protected $_elementFactory;


	/**
	 * @var $_attributesRenderer \Frontuser\Integration\Block\Adminhtml\Form\Field\Activation
	 */
	protected $_activation;

	/**
	 * @param \Magento\Backend\Block\Template\Context $context
	 * @param \Magento\Framework\Data\Form\Element\Factory $elementFactory
	 * @param array $data
	 */
	public function __construct(
		\Magento\Backend\Block\Template\Context $context,
		\Magento\Framework\Data\Form\Element\Factory $elementFactory,
		array $data = []
	)
	{
		$this->_elementFactory  = $elementFactory;
		parent::__construct($context,$data);
	}


	/**
	 * Get activation options.
	 *
	 * @return \Frontuser\Integration\Block\Adminhtml\Form\Field\Activation
	 */
	protected function _getActivationRenderer()
	{
		if (!$this->_activation) {
			$this->_activation = $this->getLayout()->createBlock(
				'\Frontuser\Integration\Block\Adminhtml\Form\Field\Activation',
				'',
				['data' => ['is_render_to_js_template' => true]]
			);

			$objectManager =  \Magento\Framework\App\ObjectManager::getInstance();
			$attributes = $objectManager->get('Magento\Customer\Model\Customer')->getAttributes();

			$attributesArrays = array();
			foreach($attributes as $attribute) {
				$attributesArrays[] = array(
					'label' => $attribute->getName(),
					'value' => $attribute->getName()
				);
			}
			$this->_activation->setOptions($attributesArrays);
		}

		return $this->_activation;
	}


	/**
	 * Prepare to render.
	 *
	 * @return void
	 */
	protected function _prepareToRender()
	{
		$this->addColumn('field', ['label' => __('Custom Field')]);

		$this->addColumn('value', [
			'label' => __('Magento Field'),
			'renderer' => $this->_getActivationRenderer()
		]);

		$this->_addAfter = false;
		$this->_addButtonLabel = __('Add');
	}


	/**
	 * Prepare existing row data object.
	 *
	 * @param \Magento\Framework\DataObject $row
	 * @return void
	 */
	protected function _prepareArrayRow(\Magento\Framework\DataObject $row)
	{
		$options = [];
		$customAttribute = $row->getData('value');

		$key = 'option_' . $this->_getActivationRenderer()->calcOptionHash($customAttribute);
		$options[$key] = 'selected="selected"';
		$row->setData('option_extra_attrs', $options);
	}
}