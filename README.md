# ScoreCheck-NHL

Example request:

check_score.php?teamId=7&eventName=sabres_score

check_score.php?teamId=14&eventName=lightning_score

A score for provided team ID will create Particle event with the provided event name.

Example CURL triggers:

curl https://api.particle.io/v1/devices/events \
	-d "name=sabres_score" \
	-d "access_token=7be634f544f6b0f8348308d6b62d01b588453f07" \
    -d "private=true"

curl https://api.particle.io/v1/devices/events \
	-d "name=lightning_score" \
	-d "access_token=7be634f544f6b0f8348308d6b62d01b588453f07" \
    -d "private=true"