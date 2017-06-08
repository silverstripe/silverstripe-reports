<div class="cms-mobile-menu-toggle-wrapper"></div>
<div id="reportadmin-cms-content" class="flexbox-area-grow fill-height cms-content cms-tabset $BaseCSSClasses" data-layout-type="border" data-pjax-fragment="Content">

	<div class="cms-content-header">
		<% with $EditForm %>
			<div class="cms-content-header-info">
				<% include SilverStripe\\Admin\\BackLink_Button %>
				<% with $Controller %>
					<% include SilverStripe\\Admin\\CMSBreadcrumbs %>
				<% end_with %>
			</div>
		<% end_with %>
	</div>

	<div class="flexbox-area-grow cms-content-fields ui-widget-content" data-layout-type="border">

		$EditForm

	</div>

</div>
