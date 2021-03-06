<?php

/**
 * Class MassExportForm
 */
class MassExportForm extends Form
{

    /**
     * MassExportForm constructor.
     *
     * @param Controller $controller
     * @param string $name
     */
    public function __construct($controller, $name)
    {
        $dt = new DateTime();
        $today = $dt->format('Y-m-d');

        $fields = FieldList::create(
            LiteralField::create('DateSelectionHeading', '<h2>Export Dates</h2>'),
            DateField::create('exportFrom', 'From')->setConfig('showcalendar', true)->setConfig('max', $today),
            DateField::create('exportTo', 'To')->setConfig('showcalendar', true)->setConfig('max', $today),

            LiteralField::create('ExportConfigHeading', '<h2>Export Configuration</h2>'),
            ListboxField::create('modelsToExport', 'Models to Export', $this->generateModelList())->setMultiple(true)->setRightTitle('Models will be exported individually and then zipped, they will not be consolidated')
        );

        $actions = FieldList::create(
            FormAction::create('processExportForm', 'Export & Download')/*,
            FormAction::create('exportEmail', 'Export & Email')
                ->setUseButtonTag(true)
                ->setAttribute('onclick', 'return false;')
                ->addExtraClass('massexport-email')*/
        );

        $validator = RequiredFields::create(
            array(
                'exportFrom',
                'exportTo',
                'modelsToExport'
            )
        );

        parent::__construct($controller, $name, $fields, $actions, $validator);
    }

    /**
     * Generates a mapped list of ID => Model Name
     */
    public function generateModelList()
    {
        $modelAdmins = array_reverse(array_keys(ClassInfo::subclassesFor('ModelAdmin'))); // put my thang down flip it and reverse it
        array_pop($modelAdmins); // gets rid of the ModelAdmin class

        $map = array();

        foreach ($modelAdmins as $modelAdmin) {
            $managedModels = $modelAdmin::config()->managed_models;
            if (!$managedModels) {
                continue;
            }

            foreach ($managedModels as $model) {
                $map[$model] = implode(' ', preg_split('/(?=[A-Z])/', $model));
            }
        }

        $map = array_merge($map, $this->generateUserDefinedFormsList());

        return $map;
    }

    /**
     * Generates a readable equivalent of UDF_{PageID} => PageTitle: User Defined Form
     * The UDF_ part should be detected in the post handling and handled accordingly
     *
     * @return array
     */
    public function generateUserDefinedFormsList()
    {
        if (!class_exists('UserDefinedForm')) {
            return array();
        }

        $map = array();
        $pageIds = array_keys(SubmittedForm::get()->map('ParentID', 'ParentID')->toArray());

        foreach ($pageIds as $pageId) {
            $record = SiteTree::get()->byID($pageId);
            $map['UDF_' . $pageId] = 'User Defined Form Submissions: ' . $record->Title;
        }

        return $map;
    }
}