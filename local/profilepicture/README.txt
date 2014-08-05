Replace the filepicker on the user profile edit page with an 'avatar selector'.

Installation:

1. Unzip the files to [moodlecode]/local/profilepicture
2. Add a selection of images to local/profilepicture/images (see local/profilepicture/images/README.txt for details)
3. Modify the file user/edit_form.php:

Find these lines:
                $imageelement = $mform->getElement('currentpicture');
                $imageelement->setValue($imagevalue);

Just BEFORE them, add this line:
                $imagevalue = html_writer::tag('span', $imagevalue, array('id' => 'currentpicture'));

4. Modify the file user/editlib.php:

Find these lines:
        $mform->addElement('filemanager', 'imagefile', get_string('newpicture'), '', $filemanageroptions);
        $mform->addHelpButton('imagefile', 'newpicture');

REPLACE them with:
        if (has_capability('moodle/user:update', context_system::instance())) {
            $mform->addElement('filemanager', 'imagefile', get_string('newpicture'), '', $filemanageroptions);
            $mform->addHelpButton('imagefile', 'newpicture');
        } else {
            require_once($CFG->dirroot.'/local/profilepicture/lib.php');
            $mform->addElement('profilepicture', 'imagefile', get_string('newpicture'));
        }
