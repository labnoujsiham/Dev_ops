pipeline {
    agent any

    environment {
        DOCKER_IMAGE = 'labnoujsiham/devops-app'
        DOCKER_TAG = 'latest'
        DOCKER_CREDENTIALS = 'dockerhub-credentials'
    }

    stages {

        stage('Construction') {
            steps {
                echo 'Construction de l\'image Docker en cours...'
                sh 'docker build --no-cache -t ${DOCKER_IMAGE}:${DOCKER_TAG} .'
            }
        }

        stage('Tests') {
            steps {
                echo 'Exécution des tests en cours...'
                sh 'docker run --rm ${DOCKER_IMAGE}:${DOCKER_TAG} ./vendor/bin/phpunit tests/'
            }
        }

        stage('Publication') {
            steps {
                echo 'Publication de l\'image sur Docker Hub...'
                withCredentials([usernamePassword(
                    credentialsId: "${DOCKER_CREDENTIALS}",
                    usernameVariable: 'DOCKER_USER',
                    passwordVariable: 'DOCKER_PASS'
                )]) {
                    sh 'echo $DOCKER_PASS | docker login -u $DOCKER_USER --password-stdin'
                    sh 'docker push ${DOCKER_IMAGE}:${DOCKER_TAG}'
                }
            }
        }

        stage('Déploiement') {
            steps {
                echo 'Déploiement vers Kubernetes en cours...'
                sh 'kubectl apply -f deploiement/deployment.yaml'
                sh 'kubectl apply -f deploiement/service.yaml'
            }
        }

    }

    post {
        success {
            echo '✅ Pipeline terminé avec succès!'
        }
        failure {
            echo '❌ Le pipeline a échoué — vérifiez les logs ci-dessus.'
        }
    }
}