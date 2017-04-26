<?php
echo '<?xml version="1.0"?>';

?>
<rss xmlns:g="http://base.google.com/ns/1.0">
	<channel>
		<?php if(isset($google_feed) && is_array($google_feed)):
		foreach($google_feed as $row):?>
		<item>				
			<g:id><?php echo $row['id']; ?></g:id>
			<g:title><?php echo htmlspecialchars($row['title']); ?></g:title>
			<g:description><?php htmlspecialchars($row['description']); ?></g:description>
			<g:product_type><?php echo htmlspecialchars($row['product_type']); ?></g:product_type>
			<g:link><?php echo $row['link']; ?></g:link>
			<g:image_link><?php echo $row['image_link']; ?></g:image_link>
			<g:condition><?php echo $row['condition']; ?></g:condition>
			<g:price><?php echo $row['price']; ?></g:price>
			<g:sale_price><?php echo $row['sale_price']; ?></g:sale_price>
			<g:sale_price_effective_date><?php echo $row['sale_price_effective_date']; ?></g:sale_price_effective_date>
			<g:brand><?php echo htmlspecialchars($row['brand']); ?></g:brand>
			<g:availability><?php echo $row['availability']; ?></g:availability>
		</item>
		<?php endforeach;
		endif;?>
	</channel>
</rss>