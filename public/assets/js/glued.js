  //
  // Compile twig.js templates
  // 
  $("script[type='text/twig']").each(function() {
    var id = $(this).attr("id"),
    data = $(this).text();
    Twig.twig({ id: id, data: data, allowInlineIncludes: true, autoescape: true });
  });
