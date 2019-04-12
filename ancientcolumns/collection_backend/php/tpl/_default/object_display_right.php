<div id="pInfoAbstract" class="imgBG d-none d-md-block">
    <ul class="jump">
        <?php
        foreach ($tpl->main_sections as $i => $def):
            $v = stringFromObjectData($tpl->object_data, $def);
            if ($v == '' || is_null($v)) {
                continue;
            }
            ?>
            <li>
                <a href="#<?php echo sluggify(htmlentities($def->title)) ?>"><?php echo htmlentities($def->title); ?></a>
            </li>
            <?php
        endforeach;

        foreach ($tpl->resource_sections as $resDef):
            $resType = $resDef->resources;
            if (!isset($tpl->object_data->resources[$resType])
                || !is_array($tpl->object_data->resources[$resType])
                || count($tpl->object_data->resources[$resType]) <= 0)
            {
                continue;
            }
        ?>
            <li><a href="#<?php echo sluggify(htmlentities($resDef->title)) ?>"><?php echo htmlentities($resDef->title) ?></a></li>
        <?php

        endforeach;
        ?>
    </ul>
</div>
<?php $doi = null;
    if (isset($tpl->object_data->metadata->{"General Information"}->DOI) && $tpl->object_data->metadata->{"General Information"}->DOI):
    $doi = $tpl->object_data->metadata->{"General Information"}->DOI; ?>
    <div class="pInfoSidebar"><h3>DOI</h3>
        <p><?php echo $doi ?></p>
    </div>
<?php endif; ?>
<div class="pInfoSidebar"><h3>Citation</h3>
    <p>
        <?php
        $citArr = array($tpl->proj_meta->title);
        if (isset($tpl->object_data->metadata)){
            $metadata = $tpl->object_data->metadata;
            if (isset($metadata->{"General Information"})) {
                $generalInformation = $metadata->{"General Information"};
                
                $citArr = [
                    !empty($generalInformation->Creator)?$generalInformation->Creator:false,
                    $tpl->proj_meta->title,
                    !empty($generalInformation->Title)?$generalInformation->Title:false,
                    !empty($generalInformation->Subtitle)?$generalInformation->Subtitle:false,
                    !empty($generalInformation->{'Publication Year'})?$generalInformation->{'Publication Year'}:false,
                    !empty($generalInformation->Publisher)?$generalInformation->Publisher:false,
                    !empty($generalInformation->DOI)?"DOI: ".$generalInformation->DOI:false,
                ];
            }
        }
        
        $citation_str = "";
        $first = true;
        foreach($citArr as $citationPart){
            if ($citationPart !== FALSE){
                if (!$first){
                    $citation_str .= ", ";
                } else {
                    $first = false;
                }
                $citation_str .= $citationPart;
            }
        }

        echo $citation_str;
        ?>

        <?php if ($doi): ?>
            <br><br><a target='_blank' href='/citeproc/citeproc.html?doi=<?php echo $doi; ?>'>&rarr; Citation Formatter</a>
        <?php endif; ?>
    </p>
</div>