#! /bin/bash
# activate conda environment
sleep 3 # sleep 3 seconds
echo $HOME
source /home/$USER/anaconda3/etc/profile.d/conda.sh
conda activate base

# get environment variables
train_data=$(curl -H "Metadata-Flavor: Google" http://metadata.google.internal/computeMetadata/v1/instance/attributes/train_data)
test_data=$(curl -H "Metadata-Flavor: Google" http://metadata.google.internal/computeMetadata/v1/instance/attributes/test_data)
label=$(curl -H "Metadata-Flavor: Google" http://metadata.google.internal/computeMetadata/v1/instance/attributes/label)
models_name=$(curl -H "Metadata-Flavor: Google" http://metadata.google.internal/computeMetadata/v1/instance/attributes/models_name)
eval_metric=$(curl -H "Metadata-Flavor: Google" http://metadata.google.internal/computeMetadata/v1/instance/attributes/eval_metric)
prediction=$(curl -H "Metadata-Flavor: Google" http://metadata.google.internal/computeMetadata/v1/instance/attributes/prediction)
problem_type=$(curl -H "Metadata-Flavor: Google" http://metadata.google.internal/computeMetadata/v1/instance/attributes/problem_type)
split_type=$(curl -H "Metadata-Flavor: Google" http://metadata.google.internal/computeMetadata/v1/instance/attributes/split_type)
PREPROCESS=$(curl -H "Metadata-Flavor: Google" http://metadata.google.internal/computeMetadata/v1/instance/attributes/PREPROCESS)
basic_features_engineering=$(curl -H "Metadata-Flavor: Google" http://metadata.google.internal/computeMetadata/v1/instance/attributes/basic_features_engineering)
PRESET=$(curl -H "Metadata-Flavor: Google" http://metadata.google.internal/computeMetadata/v1/instance/attributes/PRESET)
time_limit=$(curl -H "Metadata-Flavor: Google" http://metadata.google.internal/computeMetadata/v1/instance/attributes/time_limit)
hypertune=$(curl -H "Metadata-Flavor: Google" http://metadata.google.internal/computeMetadata/v1/instance/attributes/hypertune)
hyperparameter_num_trials=$(curl -H "Metadata-Flavor: Google" http://metadata.google.internal/computeMetadata/v1/instance/attributes/hyperparameter_num_trials)
load_predictor=$(curl -H "Metadata-Flavor: Google" http://metadata.google.internal/computeMetadata/v1/instance/attributes/load_predictor)
pubsub_topic=$(curl -H "Metadata-Flavor: Google" http://metadata.google.internal/computeMetadata/v1/instance/attributes/pubsub_topic)
project_id=$(curl -H "Metadata-Flavor: Google" http://metadata.google.internal/computeMetadata/v1/instance/attributes/project_id)
uuid_str=$(curl -H "Metadata-Flavor: Google" http://metadata.google.internal/computeMetadata/v1/instance/attributes/uuid_str)
load_predictor_path=$(curl -H "Metadata-Flavor: Google" http://metadata.google.internal/computeMetadata/v1/instance/attributes/load_predictor_path)

# start job
cd $HOME && python3 $HOME/train.py --train_data $train_data --test_data $test_data --label $label --models_name $models_name --eval_metric $eval_metric --prediction $prediction --problem_type $problem_type --split_type_ $split_type --PREPROCESS $PREPROCESS --basic_features_engineering $basic_features_engineering --PRESET $PRESET --time_limit $time_limit --hypertune $hypertune --hyperparameter_num_trials $hyperparameter_num_trials --load_predictor $load_predictor --pubsub_topic $pubsub_topic --project_id $project_id --uuid_str $uuid_str --load_predictor_path $load_predictor_path
