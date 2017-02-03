# Tracking Infos for Frenet

This is a upgrade for get the tracking infos in API of Frenet, preventing the carrier selected at the end of the purchase from being tracked as well as differentiating the tracking identifier (Note number or Tracking Code).

BONUS: Standardize the location name of result using the code:

```$location = ucfirst(strtolower($event['EventLocation']));
$location = substr_replace($location, strtoupper(substr($location, -2)), -2);```
