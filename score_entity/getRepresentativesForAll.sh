for i in $(seq 0 50);
do
    echo $i;
    python representativeTweets.py $i --common > reps/rep_$i.csv
done
