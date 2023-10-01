<?php

// Every file should have GPL and copyright in the header - we skip it in tutorials but you should not skip it for real.

// This line protects the file from being accessed by a URL directly.                                                               
defined('MOODLE_INTERNAL') || die();

// We will add callbacks here as we add features to our theme.
function theme_h5pmodscss_get_main_scss_content($theme)
{
  global $CFG;

  $scss = '';
  $filename = !empty($theme->settings->preset) ? $theme->settings->preset : null;
  $fs = get_file_storage();

  $context = context_system::instance();
  if ($filename == 'default.scss') {
    // We still load the default preset files directly from the boost theme. No sense in duplicating them.                      
    $scss .= file_get_contents($CFG->dirroot . '/theme/boost/scss/preset/default.scss');
  } else if ($filename == 'plain.scss') {
    // We still load the default preset files directly from the boost theme. No sense in duplicating them.                      
    $scss .= file_get_contents($CFG->dirroot . '/theme/boost/scss/preset/plain.scss');
  } else if ($filename && ($presetfile = $fs->get_file($context->id, 'theme_h5pmodscss', 'preset', 0, '/', $filename))) {
    // This preset file was fetched from the file area for theme_h5pmodscss and not theme_boost (see the line above).                
    $scss .= $presetfile->get_content();
  } else {
    // Safety fallback - maybe new installs etc.                                                                                
    $scss .= file_get_contents($CFG->dirroot . '/theme/boost/scss/preset/default.scss');
  }
  // Pre CSS - this is loaded AFTER any prescss from the setting but before the main scss.                                        
  $pre = file_get_contents($CFG->dirroot . '/theme/h5pmodscss/scss/pre.scss');
  // Post CSS - this is loaded AFTER the main scss but before the extra scss from the setting.                                    
  $post = file_get_contents($CFG->dirroot . '/theme/h5pmodscss/scss/post.scss');
  // Combine them together.                                                                                                    
  return $pre . "\n" . $scss . "\n" . $post;
}

/**
 * Serves any files associated with the theme settings.
 *
 * @param stdClass $course
 * @param stdClass $cm
 * @param context $context
 * @param string $filearea
 * @param array $args
 * @param bool $forcedownload
 * @param array $options
 * @return mixed
 */
function theme_h5pmodscss_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = array()) {
  $theme = theme_config::load('h5pmodscss');

  if ($context->contextlevel != CONTEXT_SYSTEM) {
    send_file_not_found();
  }

  if ($filearea == 'hvp') {
    theme_h5pmodscss_serve_hvp_css($args[1], $theme);
  }
}

/**
 * Serves the H5P Custom CSS.
 *
 * @param string $filename The filename.
 * @param theme_config $theme The theme config object.
 */
function theme_h5pmodscss_serve_hvp_css($filename, $theme) {
  global $CFG, $PAGE;
  require_once($CFG->dirroot.'/lib/configonlylib.php'); // For min_enable_zlib_compression().

  $PAGE->set_context(context_system::instance());
  $themename = $theme->name;

  $content = get_config('theme_h5pmodscss', "scssh5p");
  $md5content = md5($content);
  $md5stored = get_config('theme_'.$themename, 'hvpccssmd5');
  if ((empty($md5stored)) || ($md5stored != $md5content)) {
      // Content changed, so the last modified time needs to change.
      set_config('hvpccssmd5', $md5content, 'theme_'.$themename);
      $lastmodified = time();
      set_config('hvpccsslm', $lastmodified, 'theme_'.$themename);
  } else {
      $lastmodified = get_config('theme_'.$themename, 'hvpccsslm');
      if (empty($lastmodified)) {
          $lastmodified = time();
      }
  }

  // Sixty days only - the revision may get incremented quite often.
  $lifetime = 60 * 60 * 24 * 60;

  header('HTTP/1.1 200 OK');

  header('Etag: "'.$md5content.'"');
  header('Content-Disposition: inline; filename="'.$filename.'"');
  header('Last-Modified: '.gmdate('D, d M Y H:i:s', $lastmodified).' GMT');
  header('Expires: '.gmdate('D, d M Y H:i:s', time() + $lifetime).' GMT');
  header('Pragma: ');
  header('Cache-Control: public, max-age='.$lifetime);
  header('Accept-Ranges: none');
  header('Content-Type: text/css; charset=utf-8');
  if (!min_enable_zlib_compression()) {
      header('Content-Length: '.strlen($content));
  }

  echo $content;

  die;
}
