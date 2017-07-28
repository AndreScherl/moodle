# atto_clozeeditor
Plugin for the Atto text editor of Moodle LMS to create embedded answers (CLOZE) more easy like the CLOZE editor plugin for TinyMCE.

An interface that integrates with Moodles standard HTML editor Atto to support teachers in creating CLOZE (Embedded answers) questions.
It will also read and edit existing CLOZE items (both created with this plugin as well as such created with the TinyMCE clozeeditor plugin).

* icon: question (question mark)
* edit CLOZE items:
  * place cursor somewhere inside the item code, i.e. between { and }, the icon gets highlighted
  * click CLOZE editor icon, the existing parts of the item are read into the CLOZE editor
  * edit the item, then create the updated CLOZE item
* edit CLOZE items created with the TinyMCE:
  * mark the whole item code (from { to }, including the curly brackets)
  * proceed as explained above