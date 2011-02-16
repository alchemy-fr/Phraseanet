var p4 = {};

$(document).ready(function (){
/*	 //show calendar
	$(".add_date").unbind("click").bind("click", function(){
		$("#mask").show();
		$(this).closest("li").find("div.datePicker").show();
	});
	
	var $width = $(window).height();
	
	$("#dashboard-table").css('visibility','hidden').show();
	$("#dashboard-table").jSlideTouch({height: 480});
	$("#dashboard-table").hide().css('visibility','visible');
	
	
	 
	//new link jqtouch
	$('a[target="_blank"]').click(function() {
        if (confirm('This link opens in a new window.')) {
            return true;
        } else {
            $(this).removeClass('active');
            return false;
        }
    });
    
    $('#dashboard-table .link').unbind("click").bind('click', function(){
    	
        $this = $(this);
        var classn = $this.attr("class");
        var arrClass = classn.split(' ');
        info = arrClass[0].split("_");
        var rid = info[0];
        var sbasid = info[1];
        var $left = parseInt($(".wrapping").css("left"));
        $.ajax({
			type: "POST",
			url: "./ajax_table_content.php",
			dataType : "json",
			data: ({tbl : "what", rid : rid, sbasid : sbasid, collection : "", from : "DASH", dmin : date.dmin, dmax : date.dmax }),
			beforeSend:function(){
				$("#dashboard-info").find(".result").empty().addClass("loading");
			},
			success: function(data)
			{
				$(".wrapping").css("left", $left);
				$("#dashboard-info").find(".result").empty().append(data.rs);
			}
		});
	});
	
	$("#settings li a ").unbind("click").bind("click", function(){
		var div = $(this).attr("class");
		$select = $("#" + div);
		$left = (-1) * parseInt($select.css("left"));
		
		$(".wrapping").css("left", $left);
	});
	
	$(".formsubmiter").unbind("click").bind("click", function(){
		classs = $(this).attr("class");
		arr = classs.split(" ");
		var tbl = arr[1];
		var form = $(this).closest("form");
		form.find("input[name=tbl]").val(tbl);
	});
	
	function simulateClick(item) 
	{
		if(item.click) 
		{
			item.click();
		}
		else if(item.nodeName == "A") 
		{
			window.location = item.href;
		}
		else 
		{
			$(item).click();
		}
	}
	
	//Dashboard 1st page ul submiter
	/*$("#dashboard-table ul li").unbind("click").bind("click", function(e){
		var elmtLauncher = null;
		if(elmtLauncher == null)
		{
			//stop l'evenement
			e.preventDefault();
		    //stock l'evenement
		      elmtLauncher = $(this);
		      var data = $(this).data("info");
				$.ajax({
					type: "POST",
					url: "./ajax_table_content.php",
					dataType : "json",
					data: ({tbl : "CNX", sbasid : data.id, collection : data.coll, dmin : data.dmin, dmax : data.dmax }),
					success: function(data)
					{
					 	$('body').append("<div id='CNX'>" + data.rs + "</div>");
					}
				});
			
		}
	});
	
	$(".options").unbind("click").bind("click", function(){
		content = $(this).find(":selected").html();
		
		li = $(this).closest("li");
		li.next().html("Grouper par " + content).show();
	});
	
	$(".CNX_generate").unbind("click").bind("click", function(){
		$multi = $("#report-connexions-settings").find(".multiselect");
		console.log($multi);
		$multi.find("options").attr("selected", "selected");
		$("#report-connexions-settings").find(".CNX").trigger("click");
		
	});*/
   
});//end ready document