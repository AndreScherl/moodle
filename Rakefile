require "yaml"

theme_path = "theme/mebis"
js_dir = "javascripts"
js_config = "js-config.yaml"
js_minify = "vendor.min.js"

# CSS compilation tasks.
namespace :styles do
  compass_root = theme_path

  desc "Run compass stats"
  task :stats do
    puts "*** Running compass stats ***"
    system "bundle exec compass stats " + compass_root
  end

  desc "Clean all compiled CSS files as well as the Sass cache"
  task :clean do
    puts "*** Cleaning styles and Sass cache ***"
	system "bundle exec compass clean " + compass_root
  end

  desc "Watch the styles and compile new changes"
  task :watch => ["watch:default"]

  namespace :watch do
    desc "Watch styles and compile with development settings"
    task :default => :clean do
      puts "*** Watching styles with development settings ***"
      system "bundle exec compass watch -e development " + compass_root
    end

    desc "Watch styles and compile with production settings"
    task :production => :clean do
      puts "*** Watching styles with production settings ***"
      system "bundle exec compass watch -e production " + compass_root
    end
  end

  desc "Compile new styles"
  task :compile => ["compile:default"]

  namespace :compile do
    desc "Compile styles for development"
    task :default => :clean do
      puts "*** Compiling styles ***"
      system "bundle exec compass compile -e development --debug-info " + compass_root
    end

    desc "Compile styles for production"
    task :production => :clean do
      puts "*** Compiling styles ***"
      system "bundle exec compass compile -e production --force " + compass_root
    end
  end
end

namespace :js do
  desc "View all javascript paths from the config file"
  task :config do
    puts "\033[0;36m*** View all javascript paths from the config file *** \033[0m"
    file = File.open(theme_path + "/" + js_config)
    props = YAML.load(file)
    puts props.join(" \n")
  end

  desc "Clean all compiled JS files"
  task :clean do
    puts "\033[0;36m*** Cleaning javascript *** \033[0m"
    system "rm -Rfv " + theme_path + "/" + js_dir + "/" + js_minify + " > /dev/null"
    puts "   \033[0;33m delete \033[0m " + theme_path + "/" + js_dir
  end

  desc "Compile new javascript"
  task :compile => ["compile:default"]

  namespace :compile do
    desc "Compile javascript for development"
    task :default => :clean do
      puts "\033[0;36m*** Compiling javascript *** \033[0m"
      file = File.open(theme_path + "/" + js_config)
      props = YAML.load(file)
      path = props.join(' ')
      system "cat " + path + " | uglifyjs -o " + theme_path + "/" + js_dir + "/" + js_minify
      puts "   \033[0;32m create \033[0m" + theme_path + "/" + js_dir + "/" + js_minify
    end

    desc "Compile javascript for production"
    task :production => :compile do
    end
  end
end