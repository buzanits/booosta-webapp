%if({%_goback} && {%_backpage}):
  {REDIRECT {%base_dir}{%_backpage}}
%endif;

{BBOXCENTER}
{BPANEL}

{%output}

%if({%_backpage})
<br><br>{LINK|Back|{%base_dir}{%_backpage}}

{/BPANEL}
{/BBOXCENTER}
