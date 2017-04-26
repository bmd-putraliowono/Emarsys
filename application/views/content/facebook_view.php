<?php
/**
 * Created by PhpStorm.
 * User: putra.liowono
 * Date: 5/19/2016
 * Time: 1:50 PM
 */
echo '<?xml version="1.0"?>';

?>
<rss>
    <channel>
        <?php if(isset($facebook_feed) && is_array($facebook_feed)):
            foreach($facebook_feed as $row):?>
                <item>
                    <id><?php echo $row['id']; ?></id>
                    <title><?php echo htmlspecialchars($row['title']); ?></title>
                    <description><?php echo htmlspecialchars($row['description']); ?></description>
                    <product_type><?php echo htmlspecialchars($row['product_type']); ?></product_type>
                    <link><?php echo htmlspecialchars($row['link']); ?></link>
                    <image_link><?php echo $row['image_link']; ?></image_link>
                    <condition><?php echo $row['condition']; ?></condition>
                    <price><?php echo $row['price']; ?></price>
                    <sale_price><?php echo $row['sale_price']; ?></sale_price>
                    <sale_price_effective_date><?php echo $row['sale_price_effective_date']; ?></sale_price_effective_date>
                    <brand><?php echo htmlspecialchars($row['brand']); ?></brand>
                    <availability><?php echo $row['availability']; ?></availability>
                </item>
            <?php endforeach;
        endif;?>
    </channel>
</rss>