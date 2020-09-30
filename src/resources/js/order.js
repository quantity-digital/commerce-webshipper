$(document).ready(function(){
  $(".expand").click(function(){
	let id = $(this).data('id')
	let icon = $.trim($(this).html());
	 $(this).text(icon == "+" ? "-" : "+");
    $(".shipmentData[data-id="+id+"]").slideToggle("slow");
  });
});
