<?php
/**
 * FindAndReplace_IndexController
 *
 * @copyright Copyright © 2015 Richard Doe
 * @license http://opensource.org/licenses/MIT MIT
 * @package FindAndReplace
 */
class FindAndReplace_IndexController extends Omeka_Controller_AbstractActionController
{
    /**
     * Display a form to select elements and enter find and replace text.
     */
    public function indexAction() {
        $formOptions = array('type' => 'find_and_replace');
        $form = new Omeka_Form_Admin($formOptions);
        $this->view->form = $form;
        
        $form->addElementToEditGroup('select', 'element_id', array(
            'id' => 'element-id',
            'multiOptions' => $this->_getOptionsForElementSelect(),
            'label' => __('Element'),
            'required' => true,
        ));
        $form->addElementToEditGroup('text', 'find', array(
            'label' => __('Find'),
            'required' => true,
        ));
        $form->addElementToEditGroup('text', 'replace', array(
            'label' => __('Replace'),
            'required' => true,
        ));
        
        // prevent the "Save changes" button from being added to the save area
        $form->getDisplayGroup('save')->removeDecorator('Omeka_Form_Decorator_SavePanelAction');
        
        // add a preview button to the save area
        $form->addElementToSaveGroup('submit', 'preview', array(
            'label' => __('Preview'),
            'class' => 'submit big green button'
        ));
        
        if (!$this->getRequest()->isPost()) {
            return;
        }
        
        if (!$form->isValid($this->getRequest()->getPost())) {
            $this->_helper->flashMessenger(__('Invalid form input. Please see errors below and try again.'), 'error');
            return;
        }
        
        $elementId = $this->getParam('element_id');
        $element = $this->_helper->db->getTable('Element')->find($elementId);
        $findText = $this->getParam('find');
        $replaceText = $this->getParam('replace');
        
        if ($this->getParam('preview')) {
            $form->getDisplayGroup('save')->removeElement('preview');
            $this->view->count = $this->_countMatchingElementTexts($elementId, $findText);
            
            // @todo Replace with a decorator
            $form->addElementToEditGroup('note', 'caution_note', array(
                'label' => __('Caution'),
                'value' => __('<strong>This action is irreversible!</strong>'),
            ));
            
            $form->addElementToEditGroup('note', 'element_id_note', array(
                'label' => __('Element'),
                'value' => $element->set_name. ' &gt; ' . $element->name,
            ));
            $form->addElementToEditGroup('hidden', 'element_id', array(
                'value' => $elementId
            ));
            
            $form->addElementToEditGroup('note', 'find_note', array(
                'label' => __('Find'),
                'value' => $findText,
            ));
            $form->addElementToEditGroup('hidden', 'find', array(
                'value' => $findText
            ));
            
            $form->addElementToEditGroup('note', 'replace_note', array(
                'label' => __('Replace'),
                'value' => $replaceText,
            ));
            $form->addElementToEditGroup('hidden', 'replace', array(
                'value' => $replaceText
            ));
            
            $form->addElementToEditGroup('note', 'count_note', array(
                'label' => __('Affected records'),
                'value' => $this->view->count,
            ));
            
            $form->addElementToSaveGroup('submit', 'confirm', array(
                'label' => __('Find And Replace!'),
                'class' => 'submit big red button',
            ));
        } else if ($this->getParam('confirm')) {
            $count = $this->_findAndReplace($elementId, $findText, $replaceText);
            $this->_helper->flashMessenger(__('Element text found and replaced in %d record(s)', $count), 'success');
            $this->_helper->redirector('index');
        }
        return;
    }
    
    // @todo Move this into a background job
    private function _findAndReplace($elementId, $findText, $replaceText) {
        $db = get_db();

        $elementTextTable = $db->getTable('ElementText');
        $elementTextSelect = $elementTextTable->getSelect()
            ->where('element_id = ?', $elementId)
            ->where('text = ?', $findText);

        $pageNumber = 1;
        $replacedCount = 0;
        
        // Query a limited number of rows at a time to prevent memory issues.
        while ($elementTexts = $elementTextTable->fetchObjects($elementTextSelect->limitPage($pageNumber, 100))) {
            foreach ($elementTexts as $elementText) {
                $elementText->setText($replaceText);
                $elementText->save();
                
                $recordTable = $db->getTable($elementText->record_type);
                $recordSelect = $recordTable->getSelect()
                    ->where('id = ?', $elementText->record_id);
                $record = $recordTable->fetchObject($recordSelect);
                
                if (is_callable(array($record, 'addSearchText'))) {
                    // The record implements the search mixin.
                    // Save the record object, which indexes its search text.
                    $record->save();
                }
                release_object($record);
                
                $replacedCount++;
            }
            $pageNumber++;
        }
        
        return $replacedCount;
    }
    
    private function _countMatchingElementTexts($elementId, $findText) {
        $db = get_db();
        $table = $db->getTable('ElementText');
        $count = $table->count(array(
            'element_id' => $elementId,
            'text' => $findText,
        ));
        return $count;
    }
    
    private function _getOptionsForElementSelect()
    {
        $elements = $this->_findElementsForSelect();
        $options = array('' => __('Select Below'));
        foreach ($elements as $element) {
            $optGroup = __($element['set_name']);
            $value = __($element['name']);
            $options[$optGroup][$element['id']] = $value;
        }
        return $options;
    }
    
    private function _findElementsForSelect()
    {
        $db = $this->_helper->db;
        $table = $db->getTable('Element');
        $select = $table->getSelect()
            ->order(array('element_sets.name', 'elements.name'));

        return $table->fetchAll($select);
    }
}
