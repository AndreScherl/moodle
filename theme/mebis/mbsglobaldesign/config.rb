Encoding.default_external = "utf-8"
# Require any additional compass plugins here.
require 'bootstrap-sass'

# Set this to the root of your project when deployed:
http_path = "/"
css_dir = "../style"
sass_dir = "scss"
images_dir = "../pix"
javascripts_dir = "javascripts"

# add path to moodle specific scss files (will be different for each developer)
add_import_path "C:\\Users\\Scherl\\Documents\\localhost-htdocs\\mbsredesign\\theme\\mebis\\scss"

# You can select your preferred output style here (can be overridden via the command line):
# output_style = :expanded or :nested or :compact or :compressed

# To enable relative paths to assets via compass helper functions. Uncomment:
# relative_assets = true

# To disable debugging comments that display the original location of your selectors. Uncomment:
line_comments = false

preferred_syntax = :scss

# You can select your preferred output style here (can be overridden via the command line):
# output_style = :expanded or :nested or :compact or :compressed
output_style = (environment == :production) ? :compressed : :expanded

# To enable relative paths to assets via compass helper functions. Since Drupal
# themes can be installed in multiple locations, we don't need to worry about
# the absolute path to the theme from the server root.
relative_assets = true

# To disable debugging comments that display the original location of your selectors. Uncomment:
# line_comments = (environment == :production) ? false : true

# Pass options to sass. For development, we turn on the FireSass-compatible
# debug_info if the firesass config variable above is true.
# sass_options = (environment != :production) ? {:debug_info => true} : {}
