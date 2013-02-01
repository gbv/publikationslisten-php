var insert = function(){
	var citeproc, output;
    var locales = { };

$(document).ready(function() {

    var styleName = "ieee";
    var cql       = "";
    var dbkey     = "gvk";
    var language  = "de-DE";

    $.when(
        $.ajax('./load.php?cql='+cql+'&dbkey='+dbkey),
        $.ajax('./load.php?style='+styleName),
        $.ajax('./load.php?locale='+language) //,
//        $.ajax('./load.php?abbreviations=default)
    ).done(function(citesData,styleData,localeData){
        data = citesData[0];

        for (var lang in localeData[0].locales) {
            locales[lang] = localeData[0].locales[lang];
        }

        var sys = {
            retrieveItem : function(id) { return data[id]; },
            retrieveLocale : function(lang) { return locales[lang]; }
        };
        var style = styleData[0].style;

        citeproc = new CSL.Engine(sys,style,language);
        citeproc.updateItems(["ITEM-1", "ITEM-2", "ITEM-3", "ITEM-4", "ITEM-5", "ITEM-6"]);
        output = citeproc.makeBibliography();
        if (output && output.length && output[1].length){
            output = output[0].bibstart + output[1].join("") + output[0].bibend;
            $('#ieee').html( output );
        }

    });
});
};
